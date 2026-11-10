# CVE policy `vendor-priority-v3`

How openITCOCKPIT classifies a CVE finding, and why. The rules are deterministic, no
machine learning is involved, so every classification can be explained.

- Code: `src/itnovum/openITCOCKPIT/Vuln/Policy/`, namespace `itnovum\openITCOCKPIT\Vuln\Policy`
- Tests: `tests/TestCase/Vuln/Policy/`, every row of the tables below is covered
- Run the tests: `vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/TestCase/Vuln/Policy`

The classes only use PHP itself, no CakePHP and no database. Everything they need is passed in.

## Principles

- The traffic light shows the need for action, not security. Green does not mean free of CVEs.
- A matching package does not prove that the vulnerability can be exploited in practice.
- No finding is hidden because of its age.
- Unknown is not harmless. Missing data results in `not_evaluable`, never in 0 CVEs.
- An available update does not prove a fix if the vendor says "not fixed yet".

## Codes instead of texts

A classification consists of a **rule** (`Rule`, e.g. `high_priority`) and **reasons** as bit
flags (`Reason`). The reasons contain every fact that applies to the finding, not only the one
that decided the rule. The frontend translates both into text.

Reasons are combined with `|` and checked with `Reason::has()`. Do not use `^` (XOR): it toggles
a bit, so adding the same reason twice would remove it.

| Bit | Value | Reason |
| --- | --- | --- |
| 0 | 1 | `CISA_KEV`: CISA lists the CVE as exploited in the wild |
| 1 | 2 | `VULNCHECK_KEV`: VulnCheck reports exploitation |
| 2 | 4 | `WEAPONIZED_EXPLOIT`: Metasploit module, Nuclei template or Exploit-DB entry |
| 3 | 8 | `PROOF_OF_CONCEPT`: only a proof of concept exists, does not escalate on its own |
| 4 | 16 | `HIGH_PRIORITY`: High or Critical |
| 5 | 32 | `PRIORITY_UNKNOWN`: nobody rates the CVE |
| 6 | 64 | `KERNEL_NOT_RUNNING`: installed kernel that is not running |
| 7 | 128 | `RUNNING_KERNEL_UNKNOWN`: kernel package, the running kernel is unknown |
| 8 | 256 | `FIX_KNOWN`: a fixed version is known |
| 9 | 512 | `UPDATE_AVAILABLE`: the package manager of the host offers an update |
| 10 | 1024 | `VENDOR_NOT_FIXED`: the vendor says "not fixed yet" |
| 11 | 2048 | `PRODUCT_MATCH`: matched by product name and version (CPE), no fix data |
| 12 | 4096 | `DECISION_ACCEPTED` |
| 13 | 8192 | `DECISION_NOT_AFFECTED` |
| 14 | 16384 | `DECISION_IN_PROGRESS` |
| 15 | 32768 | `DECISION_EXPIRED`: review date or due date reached |
| 16 | 65536 | `DECISION_SEVERITY_CHANGED` |
| 17 | 131072 | `DECISION_FIX_CHANGED` |
| 18 | 262144 | `DECISION_VENDOR_STATE_CHANGED` |
| 19 | 524288 | `DECISION_POLICY_CHANGED` |

Example: rule `high_priority` with reasons 784 = 16 + 256 + 512 means high priority on active
code, a fix is known and an update is available. Rule `no_fix` with
`UPDATE_AVAILABLE | VENDOR_NOT_FIXED` explains why an available update did not count.

The values are stored in the database. Never change or reuse the value of an existing reason,
only add new ones. `Reason::toNames()` returns the names of a value, e.g. for logs.

## Classification

`FindingClassifier` checks the rules in this order, the first matching rule wins.

| Rank | Rule | Condition | View | Color |
| --- | --- | --- | --- | --- |
| 1 | `cisa_kev` | CISA KEV on active code | Action | Red |
| 2 | `weaponized_exploit` | Priority High or above, active code, Metasploit, Nuclei or Exploit-DB | Action | Red |
| 3 | `high_priority` | Priority High or above, active code | Action | Red |
| 4 | `vulncheck_kev` | VulnCheck KEV without CISA KEV, active code | Action | Yellow |
| 5 | `inactive_kernel` | Kernel that is not running, with High or KEV | Action | Yellow |
| 6 | `priority_unknown` | Priority unknown | Action | Yellow |
| 6 | `review_required` | A decision ended, see [Decisions](#decisions) | Action | Yellow |
| 7 | `remedy_available` | Lower priority, a fix or an update is known | Action | Yellow |
| 8 | `no_fix` | Lower priority, no fix | Unfixed | Gray |
| 9 | `accepted` | Risk accepted by a human | Accepted | Gray |
| 10 | `not_affected` | Checked by a human: not affected | Not affected | Gray |

The rank orders findings by urgency and is stored in `cve_host_findings.rule_rank`. Two rules
share rank 6, both are yellow tasks a human has to look at.

**Active code:** everything except kernel packages that are proven to be not running. If the
running kernel is unknown, the code counts as active (reason `RUNNING_KERNEL_UNKNOWN`).

**Exploits:** proof of concepts from GitHub or Trickest do not escalate. On an AlmaLinux 9 test
host they existed for 347 of 983 CVEs, so they tell nothing about the risk. They are only shown
as reason `PROOF_OF_CONCEPT`.

**Product matches:** applications match by product name and version (CPE). The feed contains no
fix for them, so `no_fix` with `PRODUCT_MATCH` means "fix unknown", not "no fix exists".

## Priority

`PriorityResolver` uses the first available source. The source is always shown
(`PrioritySource`), because a vendor priority means something else than a CVSS score.

| Platform | Vendor source | Code |
| --- | --- | --- |
| Ubuntu | Ubuntu priority | `ubuntu` |
| Debian, Raspbian | Debian Security Tracker, mostly only `low` and `unimportant` | `debian` |
| RHEL, CentOS | Red Hat priority per CVE | `redhat` |
| AlmaLinux, Rocky, Oracle, Amazon, Fedora | Highest erratum, rates an update, not a single CVE | `erratum` |
| Windows | Microsoft MSRC | `microsoft` |
| openSUSE, SLES, SLED | SUSE rating | `suse` |
| macOS, applications | none | |

Without a vendor rating the CVSS score is used, in this order: NVD (`nvd`), VulnCheck
(`vulncheck`), MITRE (`mitre`), EUVD (`euvd`), Red Hat (`redhat_cvss`). CVSS v4 is preferred
over v3. Without any rating the source is `unknown` and rule `priority_unknown` applies.

Why the chain: on a Debian 13 test host the Debian tracker rated only 176 of 1322 CVEs.
NVD alone would have left 412 CVEs unrated, with the chain 39 remain.

Bands: from 9.0 Critical, from 7.0 High, from 4.0 Medium, below Low. Debian `unimportant` and
`negligible` become severity None and count as Low.

## Operating system of applications

Product matches know no operating system: "Chrome on Android" also matches the Chrome installed
on Windows. `PlatformFilter` therefore reads the description of the CVE, for Windows and macOS,
in both directions.

The filter reads the description with the names of the CPE product the finding belongs to, from the
table `cve_cpe_product_names`. A Chrome CVE is read with "Google Chrome", an Edge CVE with "Microsoft Edge"
and "Microsoft Edge (Chromium-based)". The seed creates names for every default product whose CVEs
name platforms, e.g. Zoom, Sourcetree, FortiClient or Microsoft Teams. Customers can add their own software.
The name has to be entered as written in CVE descriptions, upper and lower case do not matter.
Products without names are never filtered. Every product is read the same way:

1. Find "<product> for|on <platforms>", lists with comma or "and" included
2. Keep the finding if the product is also named without platform. Versions like
   "fixed in Firefox 153" do not count as mention
3. Keep the finding if the platform of the host is one of the named platforms

A CVE gets filtered only if the description limits the product to other platforms exclusively.
In case of doubt the finding stays: a false alarm costs time, a swallowed finding leaves a hole open.

| Description | On Windows |
| --- | --- |
| Google Chrome on Android prior to … | filtered |
| Google Chrome on Mac, iOS prior to … | filtered, stays on macOS |
| Google Chrome on Windows prior to … | stays, filtered on macOS |
| Google Chrome prior to … | stays |
| Firefox for Android. … fixed in Firefox 153 | filtered |
| Firefox and Firefox Focus for Android | stays, the desktop version can be affected |
| Firefox < 150 and Firefox for Android < 150 | stays |

Findings of the operating system and Linux packages are never filtered. Filtered CVEs do not
count as findings, but are listed per host in the coverage (`cve_host_coverage`, type
`other_platform_cve`).

## Host state

`HostRules` derives the color of a host from its findings and adds the freshness of the data.

| Status (`HostStatus`) | Color | Shown as | Condition |
| --- | --- | --- | --- |
| `evaluated` or `partial` | red | Urgent | At least one red finding that needs action |
| `evaluated` or `partial` | yellow | Action required | At least one yellow finding that needs action |
| `evaluated` or `partial` | green | No priority | Neither, explicitly no proof of security |
| `stale` | gray | Stale | Inventory older than 48 hours or Vuls database older than 7 days |
| `not_evaluated` | gray | Not evaluated | Nothing could be evaluated, the reason is in the coverage |
| `failed` | last color | Evaluation failed | The last evaluation failed, the last result stays visible |

A failed evaluation never hides red findings of the last successful one. Stale findings stay
visible, only the color turns gray: green with old data would only mean that an old database
knows no CVEs. Both limits are parameters of `HostRules`.

All counters of a host count distinct CVEs, not packages (`HostTotals`). A CVE counts as not
affected only if every affected package has been checked, and as accepted only if the risk of
every affected package is accepted.

## Decisions

A decision (`cve_decisions`) applies to exactly one finding: host, CVE, component and version.
Owner, reason and a review date in the future are required, "not affected" also needs a link to
the analysis.

### Accepted and not affected

The finding gets rule `accepted` or `not_affected` (gray). The decision ends automatically,
without a new Vuls check:

- on the review date (`DECISION_EXPIRED`),
- as soon as severity, fixed version or vendor state differ from the state the decision was
  made for (`DECISION_SEVERITY_CHANGED`, `DECISION_FIX_CHANGED`, `DECISION_VENDOR_STATE_CHANGED`),
- when the version of the policy changes (`DECISION_POLICY_CHANGED`).

The finding then gets its normal classification back, at least the yellow task
`review_required`. A finding that needs action keeps its rule, so a red finding stays red.

### In progress

"Confirmed, in progress" is no exception but a mark. Owner, reason and a due date in the future
are required, a link is not.

- The finding keeps its rule, view and color. A red finding stays red, still counts as action
  required and the host keeps its color.
- If severity, fix or policy version change, the mark stays: the work on the finding goes on.
- On the due date the mark ends. The finding then requires a new decision, at least as the
  yellow task `review_required`.
- Per host, `in_progress_count` counts the CVEs with at least one finding in progress.

Missing fixes or missing findings never create a decision on their own.

## Life cycle

Only a complete evaluation (`complete`) resolves findings that were not reported anymore. Partial
and failed evaluations change nothing, missing data is no proof for a fix. A resolved finding
that gets reported again counts as reopened. Packages are separate findings per version, so an
update resolves the finding of the old version.

Operating system findings must not depend on the build: a new build keeps `first_seen` as long as
the CVE stays open.

## Recommendations

`ActionAdvisor` returns an `Action` code, optionally with the version to install, e.g.
`install_update` with version `2.0`, `remove_or_update_kernel` or `watch_vendor` if nothing can be
done. For a component across several hosts it returns e.g. `update_kernel_and_reboot` or
`update_package`.

## Policy version

`PolicyVersion::CURRENT` is part of the fingerprint of an evaluation and of every decision. A new
version evaluates all hosts again and ends accepted and not affected decisions. Change it when a
rule changes the classification of findings, not for changes that only add information.

## Known limits

- **Operating system of applications:** the filter only reads the description and only for
  products with names in `cve_cpe_product_names`. Unclear descriptions stay findings, texts like
  "python on Windows" are not read.
- **Old CVEs without version:** some old CVEs match every version, e.g. `CVE-1999-0590` for
  macOS. Firefox has 19 of them.
- **Kernel:** for Debian and Ubuntu, Vuls only evaluates the packages of the running kernel.
- **Windows:** only the operating system, evaluated by its build without KB lists. .NET is not
  evaluated, Office only through the list of applications.
- **macOS:** applications only with agent 3.8.0 or later. Apple apps and the rules for other applications
  (`cve_cpe_mappings`) use the bundle identifier, names of macOS apps are translated and not unique.
  Older agents get only the operating system evaluated.
- **Applications:** only applications with a CPE rule (`cve_cpe_mappings`) are checked. All others
  are listed as unmapped in the coverage of the host. 0 findings is no proof.
- **Not checked:** runtime, configuration, drivers or reachability.
