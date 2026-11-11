<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
//
// This file is dual licensed
//
// 1.
//     This program is free software: you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation, version 3 of the License.
//
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.
//
//     You should have received a copy of the GNU General Public License
//     along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//     If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//     under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//     License agreement and license key will be shipped with the order
//     confirmation.

namespace itnovum\openITCOCKPIT\InitialDatabase;

use App\Model\Table\CveCpeProductsTable;
use itnovum\openITCOCKPIT\Vuln\Cpe\CpeCondition;

/**
 * Class CveCpeProduct
 *
 * Default CPE products with their rules for installed applications and their names in CVE descriptions.
 *
 * The seed runs with every update. It only creates products that do not exist yet. Existing products,
 * their rules and names are never touched, so changes of users stay. A deleted default product would come back,
 * so users should disable it instead.
 *
 * @package itnovum\openITCOCKPIT\InitialDatabase
 * @property CveCpeProductsTable $Table
 */
class CveCpeProduct extends Importer {

    /**
     * @return bool
     */
    public function import() {
        foreach ($this->getData() as $record) {
            $exists = $this->Table->exists([
                'CveCpeProducts.vendor'  => $record['vendor'],
                'CveCpeProducts.product' => $record['product'],
            ]);
            if ($exists) {
                continue;
            }

            // One transaction per product, an error must not leave a product without its rules
            $this->Table->getConnection()->transactional(function () use ($record) {
                $product = $this->Table->newEntity([
                    'vendor'  => $record['vendor'],
                    'product' => $record['product'],
                    'enabled' => true,
                ]);
                $product->is_default = true;
                $this->Table->saveOrFail($product);

                foreach ($record['mappings'] as $mapping) {
                    $entity = $this->Table->CveCpeMappings->newEntity($mapping + ['cve_cpe_product_id' => $product->id]);
                    $this->Table->CveCpeMappings->saveOrFail($entity);
                }

                foreach ($record['names'] as $name) {
                    $entity = $this->Table->CveCpeProductNames->newEntity(['cve_cpe_product_id' => $product->id, 'name' => $name]);
                    $this->Table->CveCpeProductNames->saveOrFail($entity);
                }
            });
        }

        return true;
    }

    /**
     * Default products. Every CPE was checked against the Vuls database (2026-11-10): it finds at least one CVE.
     * Products whose CPE found nothing or only CVEs before 2020 are not listed, e.g. Slack, Vivaldi, OBS or Opera.
     * Rules for applications seen on real hosts were checked with their names, the others follow the names of the
     * vendor installers and always require the publisher, so they rather miss an application than match a wrong one.
     *
     * Names in CVE descriptions are only given where real CVEs name platforms, e.g. "Sourcetree for Mac".
     *
     * Some applications belong to two CPE products, e.g. Adobe Acrobat Reader: the continuous track is
     * acrobat_reader_dc, the classic track acrobat_reader. The version ranges of the CVEs tell them apart.
     *
     * @return array
     */
    public function getData() {
        $products = [];

        /*
         * Browsers
         */
        $products[] = $this->product('google', 'chrome', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'Google Chrome', 'Google'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.google.Chrome'),
        ], ['Google Chrome', 'Chrome']);

        $products[] = $this->product('mozilla', 'firefox', [
            // e.g. "Mozilla Firefox (x64 de)", but not "Mozilla Maintenance Service"
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Mozilla Firefox', 'Mozilla'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'org.mozilla.firefox'),
        ], ['Firefox', 'Firefox Focus']);

        $products[] = $this->product('microsoft', 'edge_chromium', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'Microsoft Edge', 'Microsoft'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.microsoft.edgemac'),
        ], ['Microsoft Edge', 'Microsoft Edge (Chromium-based)']);

        $products[] = $this->product('brave', 'brave', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'Brave', 'Brave Software'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.brave.Browser'),
        ]);

        /*
         * Mail, office and PDF
         */
        $products[] = $this->product('mozilla', 'thunderbird', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Mozilla Thunderbird', 'Mozilla'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'org.mozilla.thunderbird'),
        ], ['Thunderbird']);

        foreach (['acrobat_reader_dc', 'acrobat_reader'] as $product) {
            $products[] = $this->product('adobe', $product, [
                // e.g. "Adobe Acrobat Reader (64-bit)", "Adobe Acrobat Reader DC - Deutsch"
                $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Adobe Acrobat Reader', 'Adobe'),
                $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.adobe.Reader'),
            ], ['Acrobat Reader', 'Acrobat Reader DC']);
        }

        foreach (['acrobat_dc', 'acrobat'] as $product) {
            $products[] = $this->product('adobe', $product, [
                // "starts with Adobe Acrobat" would also match the Reader
                $this->windowsName(CpeCondition::MATCH_EQUALS, 'Adobe Acrobat (64-bit)', 'Adobe'),
                $this->windowsName(CpeCondition::MATCH_EQUALS, 'Adobe Acrobat DC (64-bit)', 'Adobe'),
                $this->windowsName(CpeCondition::MATCH_EQUALS, 'Adobe Acrobat DC', 'Adobe'),
                $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.adobe.Acrobat.Pro'),
            ]);
        }

        // No macOS rule: the bundle identifier is not confirmed yet
        $products[] = $this->product('foxit', 'pdf_reader', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Foxit PDF Reader', 'Foxit'),
        ]);

        $products[] = $this->product('sumatrapdfreader', 'sumatrapdf', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'SumatraPDF', 'Krzysztof Kowalczyk'),
        ]);

        $products[] = $this->product('libreoffice', 'libreoffice', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'LibreOffice', 'Document Foundation'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'org.libreoffice.script'),
        ]);

        $products[] = $this->product('apache', 'openoffice', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'OpenOffice', 'Apache'),
        ]);

        $products[] = $this->product('diagrams', 'drawio', [
            // e.g. "draw.io 28.2.8"
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'draw.io', 'JGraph'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.jgraph.drawio.desktop'),
        ]);

        /*
         * Communication
         */
        foreach (['workplace', 'zoom'] as $product) {
            $products[] = $this->product('zoom', $product, [
                // e.g. "Zoom Workplace (64-bit)" or "Zoom (64-bit)"
                $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Zoom', 'Zoom Video Communications'),
                $this->macosBundle(CpeCondition::MATCH_EQUALS, 'us.zoom.xos'),
            ], ['Zoom', 'Zoom Desktop Client', 'Zoom Workplace', 'Zoom Workplace App', 'Zoom Workplace Apps', 'Zoom Workplace Desktop App', 'Zoom Clients']);
        }

        $products[] = $this->product('microsoft', 'teams', [
            // Not "Microsoft Teams Meeting Add-in for Microsoft Office"
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'Microsoft Teams', 'Microsoft'),
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'Microsoft Teams classic', 'Microsoft'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.microsoft.teams2'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.microsoft.teams'),
        ], ['Microsoft Teams']);

        $products[] = $this->product('cisco', 'webex', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'Webex', 'Cisco'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'Cisco-Systems.Spark'),
        ], ['Webex', 'Cisco Webex']);

        $products[] = $this->product('discord', 'discord', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'Discord', 'Discord'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.hnc.Discord'),
        ]);

        $products[] = $this->product('telegram', 'telegram_desktop', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Telegram Desktop', 'Telegram'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.tdesktop.Telegram'),
        ]);

        $products[] = $this->product('signal', 'signal-desktop', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'Signal', 'Signal Messenger'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'org.whispersystems.signal-desktop'),
        ]);

        /*
         * Remote access
         */
        $products[] = $this->product('teamviewer', 'teamviewer', [
            // e.g. "TeamViewer", "TeamViewer Host"
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'TeamViewer', 'TeamViewer'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.teamviewer.TeamViewer'),
        ]);

        $products[] = $this->product('anydesk', 'anydesk', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'AnyDesk', 'AnyDesk'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.philandro.anydesk'),
        ], ['AnyDesk']);

        $products[] = $this->product('realvnc', 'vnc_viewer', [
            // e.g. "RealVNC Viewer 7.13.1"
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'RealVNC Viewer', 'RealVNC'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.realvnc.vncviewer'),
        ]);

        $products[] = $this->product('tightvnc', 'tightvnc', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'TightVNC', 'GlavSoft'),
        ]);

        $products[] = $this->product('uvnc', 'ultravnc', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'UltraVNC', 'uvnc'),
        ]);

        $products[] = $this->product('putty', 'putty', [
            // e.g. "PuTTY release 0.83 (64-bit)"
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'PuTTY release', 'Simon Tatham'),
        ]);

        $products[] = $this->product('winscp', 'winscp', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'WinSCP', 'Martin Prikryl'),
        ]);

        $products[] = $this->product('rustdesk', 'rustdesk', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'RustDesk'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.carriez.rustdesk'),
        ], ['RustDesk']);

        $products[] = $this->product('mobatek', 'mobaxterm', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'MobaXterm', 'Mobatek'),
        ]);

        $products[] = $this->product('devolutions', 'remote_desktop_manager', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Remote Desktop Manager', 'Devolutions'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.devolutions.remotedesktopmanager'),
        ], ['Devolutions Remote Desktop Manager', 'Remote Desktop Manager']);

        $products[] = $this->product('vmware', 'horizon_client', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'VMware Horizon Client', 'VMware'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.vmware.horizon'),
        ], ['VMware Horizon Client', 'Horizon Client', 'Horizon View Client']);

        $products[] = $this->product('citrix', 'workspace', [
            // The main entry is named after its release, e.g. "Citrix Workspace 2009" or "Citrix Workspace 2409".
            // Components like "Citrix Workspace (USB)" or "Citrix Workspace Inside" do not match.
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Citrix Workspace 2', 'Citrix'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.citrix.receiver.nomas'),
        ], ['Citrix Workspace', 'Citrix Workspace App']);

        /*
         * VPN
         */
        $products[] = $this->product('fortinet', 'forticlient', [
            // e.g. "FortiClient VPN", not "FortiClientUninstaller" on macOS
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'FortiClient', 'Fortinet'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.fortinet.FortiClient'),
        ], ['FortiClient', 'Fortinet FortiClient']);

        $products[] = $this->product('cisco', 'secure_client', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Cisco Secure Client', 'Cisco'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.cisco.secureclient.gui'),
        ], ['Cisco Secure Client']);

        // Former name of the Cisco Secure Client, versions before 5
        $products[] = $this->product('cisco', 'anyconnect_secure_mobility_client', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Cisco AnyConnect Secure Mobility Client', 'Cisco'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.cisco.anyconnect.gui'),
        ], ['Cisco AnyConnect Secure Mobility Client', 'AnyConnect Secure Mobility Client']);

        $products[] = $this->product('openvpn', 'connect', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'OpenVPN Connect', 'OpenVPN'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'org.openvpn.client.app'),
        ]);

        $products[] = $this->product('openvpn', 'openvpn', [
            // The community client, e.g. "OpenVPN 2.6.12-I001 amd64"
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'OpenVPN 2.', 'OpenVPN'),
        ]);

        $products[] = $this->product('wireguard', 'wireguard', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'WireGuard', 'WireGuard'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.wireguard.macos'),
        ]);

        $products[] = $this->product('paloaltonetworks', 'globalprotect', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'GlobalProtect', 'Palo Alto Networks'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.paloaltonetworks.GlobalProtect.client'),
        ], ['GlobalProtect', 'GlobalProtect Agent', 'GlobalProtect App']);

        /*
         * Archives, passwords, file transfer
         */
        $products[] = $this->product('7-zip', '7-zip', [
            // The space at the end is on purpose: "7-Zip 25.01 (x64)", but not "7-Zip-zstd"
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, '7-Zip ', 'Igor Pavlov'),
        ], ['7-Zip']);

        $products[] = $this->product('rarlab', 'winrar', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'WinRAR', 'win.rar'),
        ]);

        $products[] = $this->product('peazip', 'peazip', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'PeaZip', 'Giorgio Tani'),
        ]);

        $products[] = $this->product('keepass', 'keepass', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'KeePass Password Safe', 'Dominik Reichl'),
        ]);

        $products[] = $this->product('keepassxc', 'keepassxc', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'KeePassXC', 'KeePassXC'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'org.keepassxc.keepassxc'),
        ]);

        $products[] = $this->product('bitwarden', 'bitwarden', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Bitwarden', 'Bitwarden'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.bitwarden.desktop'),
        ]);

        $products[] = $this->product('1password', '1password', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, '1Password', 'AgileBits'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.1password.1password'),
        ]);

        $products[] = $this->product('filezilla-project', 'filezilla_client', [
            // e.g. "FileZilla 3.69.5", but not "FileZilla Server"
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'FileZilla 3', 'Tim Kosse'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'org.filezilla-project.filezilla'),
        ]);

        $products[] = $this->product('microsoft', 'onedrive', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'Microsoft OneDrive', 'Microsoft'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.microsoft.OneDrive'),
        ], ['Microsoft OneDrive', 'OneDrive']);

        /*
         * Media and graphics
         */
        $products[] = $this->product('videolan', 'vlc_media_player', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'VLC media player', 'VideoLAN'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'org.videolan.vlc'),
        ]);

        $products[] = $this->product('gimp', 'gimp', [
            // e.g. "GIMP 3.0.4"
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'GIMP ', 'GIMP'),
            $this->macosBundle(CpeCondition::MATCH_STARTS_WITH, 'org.gimp.gimp'),
        ]);

        $products[] = $this->product('inkscape', 'inkscape', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'Inkscape', 'Inkscape'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'org.inkscape.Inkscape'),
        ]);

        $products[] = $this->product('blender', 'blender', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'Blender', 'Blender Foundation'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'org.blenderfoundation.blender'),
        ]);

        $products[] = $this->product('adobe', 'photoshop', [
            // Named after the year, e.g. "Adobe Photoshop 2026". Not "Adobe Photoshop Elements", which is another product
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Adobe Photoshop 20', 'Adobe'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.adobe.Photoshop'),
        ]);

        $products[] = $this->product('adobe', 'indesign', [
            // Named after the year, e.g. "Adobe InDesign 2026"
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Adobe InDesign 20', 'Adobe'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.adobe.InDesign'),
        ]);

        // Only the new app "Affinity by Canva" (version 3 and later). Affinity Photo 2, Designer 2 and Publisher 2
        // have no CPE: mapped to this product, their versions 2.x would count as affected by every CVE "before 3.x".
        // The bundle identifier of the new app on macOS is not confirmed yet, so there is no macOS rule.
        $products[] = $this->product('canva', 'affinity', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'Affinity', 'Canva'),
        ], ['Affinity by Canva']);

        $products[] = $this->product('irfanview', 'irfanview', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'IrfanView', 'Irfan Skiljan'),
        ]);

        $products[] = $this->product('getgreenshot', 'greenshot', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Greenshot', 'Greenshot'),
        ]);

        /*
         * Virtualization and container
         */
        $products[] = $this->product('docker', 'desktop', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'Docker Desktop'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.docker.docker'),
        ], ['Docker Desktop']);

        $products[] = $this->product('vmware', 'workstation', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'VMware Workstation', 'VMware'),
        ]);

        $products[] = $this->product('vmware', 'tools', [
            // Installed on almost every VMware guest
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'VMware Tools', 'VMware'),
        ], ['VMware Tools']);

        $products[] = $this->product('vmware', 'fusion', [
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.vmware.fusion'),
        ]);

        $products[] = $this->product('oracle', 'vm_virtualbox', [
            // The name changed from "Oracle VM VirtualBox 7.0.20" to "Oracle VirtualBox 7.1.4"
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Oracle VM VirtualBox', 'Oracle'),
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Oracle VirtualBox', 'Oracle'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'org.virtualbox.app.VirtualBox'),
        ]);

        /*
         * Development
         */
        $products[] = $this->product('microsoft', 'visual_studio_code', [
            // e.g. "Microsoft Visual Studio Code (User)"
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Microsoft Visual Studio Code'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.microsoft.VSCode'),
        ]);

        $products[] = $this->product('microsoft', 'powershell', [
            // e.g. "PowerShell 7.6.4.0-x64" or "PowerShell 7-preview-x64"
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'PowerShell 7', 'Microsoft'),
        ]);

        $products[] = $this->product('microsoft', 'sql_server_management_studio', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Microsoft SQL Server Management Studio', 'Microsoft'),
        ]);

        $products[] = $this->product('microsoft', 'powertoys', [
            // e.g. "PowerToys (Preview)"
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'PowerToys', 'Microsoft'),
        ]);

        $products[] = $this->product('python', 'python', [
            // The installer adds several entries like "Python 3.13.7 Core Interpreter (64-bit)". They all have
            // the same version, so they result in the same CVEs
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Python 3.', 'Python Software Foundation'),
            // Python of the Xcode Command Line Tools, e.g. 3.9.6
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.apple.python3'),
            // Python.app of the installers of python.org
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'org.python.python'),
        ]);

        $products[] = $this->product('nodejs', 'node.js', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'Node.js'),
        ]);

        $products[] = $this->product('golang', 'go', [
            // e.g. "Go Programming Language amd64 go1.25.0"
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Go Programming Language'),
        ]);

        $products[] = $this->product('git-scm', 'git', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'Git', 'Git Development Community'),
        ], ['Git']);

        $products[] = $this->product('atlassian', 'sourcetree', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'Sourcetree', 'Atlassian'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.torusknot.SourceTreeNotMAS'),
        ], ['Sourcetree', 'Atlassian Sourcetree']);

        $products[] = $this->product('notepad-plus-plus', 'notepad++', [
            // e.g. "Notepad++ (64-bit x64)"
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Notepad++ '),
        ]);

        $products[] = $this->product('vim', 'vim', [
            // e.g. "Vim 9.1 (x64)"
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Vim '),
        ]);

        $products[] = $this->product('sublimetext', 'sublime_text_3', [
            // The Vuls database lists all CVEs of Sublime Text, also of version 4, as sublime_text_3
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Sublime Text', 'Sublime HQ'),
            $this->macosBundle(CpeCondition::MATCH_STARTS_WITH, 'com.sublimetext.'),
        ]);

        foreach ([
                     'intellij_idea' => ['IntelliJ IDEA', 'com.jetbrains.intellij'], 'pycharm' => ['PyCharm', 'com.jetbrains.pycharm'],
                     'phpstorm'      => ['PhpStorm', 'com.jetbrains.PhpStorm'], 'webstorm' => ['WebStorm', 'com.jetbrains.WebStorm'],
                     'goland'        => ['GoLand', 'com.jetbrains.goland']
                 ] as $product => [$name, $bundleId]) {
            $products[] = $this->product('jetbrains', $product, [
                $this->windowsName(CpeCondition::MATCH_STARTS_WITH, $name, 'JetBrains'),
                // Ultimate and Community editions, e.g. com.jetbrains.intellij and com.jetbrains.intellij.ce
                $this->macosBundle(CpeCondition::MATCH_STARTS_WITH, $bundleId),
            ]);
        }

        $products[] = $this->product('jetbrains', 'toolbox', [
            $this->windowsName(CpeCondition::MATCH_EQUALS, 'JetBrains Toolbox', 'JetBrains'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.jetbrains.toolbox'),
        ]);

        $products[] = $this->product('iterm2', 'iterm2', [
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.googlecode.iterm2'),
        ]);

        $products[] = $this->product('postman', 'postman', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Postman', 'Postman'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'com.postmanlabs.mac'),
        ]);

        /*
         * Network
         */
        $products[] = $this->product('wireshark', 'wireshark', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Wireshark', 'Wireshark Foundation'),
            $this->macosBundle(CpeCondition::MATCH_EQUALS, 'org.wireshark.Wireshark'),
        ]);

        $products[] = $this->product('nmap', 'nmap', [
            $this->windowsName(CpeCondition::MATCH_STARTS_WITH, 'Nmap', 'Nmap Project'),
        ]);

        return $products;
    }

    /**
     * @param string $vendor Vendor part of the CPE
     * @param string $product Product part of the CPE
     * @param array $mappings Rules of windowsName() and macosBundle()
     * @param string[] $names Names in CVE descriptions, for the PlatformFilter
     * @return array
     */
    private function product(string $vendor, string $product, array $mappings, array $names = []): array {
        return [
            'vendor'   => $vendor,
            'product'  => $product,
            'mappings' => $mappings,
            'names'    => $names,
        ];
    }

    /**
     * Windows rule on the name, optionally together with "publisher contains"
     */
    private function windowsName(string $match, string $name, ?string $publisherContains = null): array {
        if ($publisherContains === null) {
            return $this->mapping('windows', CpeCondition::FIELD_NAME, $match, $name);
        }
        return $this->mapping('windows', CpeCondition::FIELD_NAME, $match, $name, CpeCondition::FIELD_PUBLISHER, CpeCondition::MATCH_CONTAINS, $publisherContains);
    }

    /**
     * macOS rule on the bundle identifier. macOS rules never check the name: names are translated and often
     * too general, e.g. "Python" or "Docker". Agents older than 3.8.0 report no bundle identifier, their
     * applications stay unmapped.
     */
    private function macosBundle(string $match, string $bundleId): array {
        return $this->mapping('macos', CpeCondition::FIELD_BUNDLE_ID, $match, $bundleId);
    }

    private function mapping(string $platform, string $firstField, string $firstMatch, string $firstValue, ?string $secondField = null, ?string $secondMatch = null, ?string $secondValue = null): array {
        return [
            'platform'     => $platform,
            'first_field'  => $firstField,
            'first_match'  => $firstMatch,
            'first_value'  => $firstValue,
            'second_field' => $secondField,
            'second_match' => $secondMatch,
            'second_value' => $secondValue,
        ];
    }
}
