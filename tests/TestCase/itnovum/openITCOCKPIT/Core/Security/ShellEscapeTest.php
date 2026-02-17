<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today Allgeier IT Services GmbH
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

declare(strict_types=1);

namespace App\Test\TestCase\itnovum\openITCOCKPIT\Core\Security;

use App\itnovum\openITCOCKPIT\Core\Security\ShellEscape;
use Cake\TestSuite\TestCase;

/**
 * App\itnovum\openITCOCKPIT\Core\Security\ShellEscape Test Case
 *
 * How to run:
 * cd /opt/openitc/frontend
 * ./vendor/bin/phpunit --bootstrap ./config/bootstrap.php tests/TestCase/itnovum/openITCOCKPIT/Core/Security/ShellEscapeTest.php
 *
 */
class ShellEscapeTest extends TestCase {

    /**
     * Test subject
     *
     * @var \App\itnovum\openITCOCKPIT\Core\Security\ShellEscape
     */
    protected $ShellEscape;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->ShellEscape = new ShellEscape();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void {
        unset($this->ShellEscape);

        parent::tearDown();
    }

    /**
     * Test escape method
     *
     * @return void
     * @link \App\itnovum\openITCOCKPIT\Core\Security\ShellEscape::escape()
     */
    public function testEscape(): void {
        define('BS', '\\');

        // No quotes, escape all dangerous characters
        $this->assertEquals('normalstring', $this->ShellEscape->escape('normalstring'));
        $this->assertEquals('escaped\\$dollar', $this->ShellEscape->escape('escaped$dollar'));
        $this->assertEquals('escaped\\`backtick\\`', $this->ShellEscape->escape('escaped`backtick`'));
        $this->assertEquals('escaped\\&and', $this->ShellEscape->escape('escaped&and'));
        $this->assertEquals('mixed\\&and\\$dollar', $this->ShellEscape->escape('mixed&and$dollar'));
        $this->assertEquals('escaped\\;semicolon', $this->ShellEscape->escape('escaped;semicolon'));
        $this->assertEquals('escaped\\|pipe', $this->ShellEscape->escape('escaped|pipe'));
        $this->assertEquals('escaped\\<less', $this->ShellEscape->escape('escaped<less'));
        $this->assertEquals('escaped\\\\backshlash', $this->ShellEscape->escape('escaped\\\\backshlash'));
        $this->assertEquals('foo\\"bar\\"baz', $this->ShellEscape->escape('foo\\"bar\\"baz'));
        $this->assertEquals('foo\\\'bar\\\'baz', $this->ShellEscape->escape('foo\\\'bar\\\'baz'));
        $dangerousUnquoted = ['`', '$', '\\', '"', "'", ';', '&', '|', '<', '>', '(', ')', '{', '}', '[', ']', '*', '?', '~', '#', '%', '!', '=', ' '];
        $this->assertEquals('all\ we\ can\\' . implode(',\\', $dangerousUnquoted), $this->ShellEscape->escape('all we can' . implode(',', $dangerousUnquoted)));

        // Double quotes will be replaced with single quotes
        $this->assertEquals('\'normalstring\'', $this->ShellEscape->escape('"normalstring"'));
        $this->assertEquals('\'doublequoted\\$dollar\'', $this->ShellEscape->escape('"doublequoted\\$dollar"'));
        $this->assertEquals("'foo'\''bar'\''baz'", $this->ShellEscape->escape('"foo\\"bar\\"baz"'));
        $this->assertEquals("'foo'\''bar'\''baz'", $this->ShellEscape->escape('"foo\\\'bar\\\'baz"'));


        // In single quotes, only ' needs to be escaped if not already escaped
        $this->assertEquals("'is not escaped'\'''", $this->ShellEscape->escape('\'is not escaped\'\''));
        $this->assertEquals("'is already escaped'\'''", $this->ShellEscape->escape('\'is already escaped\\\'\''));
        $this->assertEquals(BS . BS, $this->ShellEscape->escape(BS)); // 1 unescaped in, 1 escaped out
        $this->assertEquals(BS . BS, $this->ShellEscape->escape(BS . BS)); // 1 escaped in, 1 escaped out
        $this->assertEquals(BS . BS, $this->ShellEscape->escape(BS . BS . BS . BS . BS . BS)); // 3 escaped in, 1 escaped out


        // Make sure we do not have an "open backslash" at the end of the string, as that can cause command injection vulnerabilities (3 in 2 out)
        $this->assertEquals(BS . BS, $this->ShellEscape->escape(BS . BS . BS)); // 1 escaped, 1 not escaped in, 1 escaped out
        $this->assertEquals("'\\\\\\'", $this->ShellEscape->escape('\'' . BS . BS . BS . '\''));

        // backticks and dollars
        $this->assertEquals("'test\`backtick\`'", $this->ShellEscape->escape('"test\`backtick\`"'));
        $this->assertEquals("'test`backtick`'", $this->ShellEscape->escape('"test`backtick`"'));
        $this->assertEquals('\'test`backtick`\'', $this->ShellEscape->escape("'test`backtick`'"));

        $this->assertEquals('AD' . BS . BS . 'Login', $this->ShellEscape->escape('AD' . BS . 'Login'));
        $this->assertEquals('\'AD' . BS . 'Login\'', $this->ShellEscape->escape('\'AD' . BS . 'Login\''));
        $this->assertEquals('\'AD' . BS . 'Login\'', $this->ShellEscape->escape('"AD' . BS . 'Login"'));

        $this->assertEquals('127.0.0.1', $this->ShellEscape->escape('127.0.0.1'));
        $this->assertEquals(sprintf('127.0.0.1%s %s`cat%s /tmp/123%s`', BS, BS, BS, BS), $this->ShellEscape->escape('127.0.0.1 `cat /tmp/123`'));
        $this->assertEquals("'127.0.0.1 '\'' `cat /tmp/123`'", $this->ShellEscape->escape('\'127.0.0.1 \' `cat /tmp/123`\''));
        $this->assertEquals("'127.0.0.1 '\'''\'''\'' `cat /tmp/123`'", $this->ShellEscape->escape('\'127.0.0.1 \'\'\' `cat /tmp/123`\''));
        $this->assertEquals("'127.0.0.1 '\'' `cat /tmp/123`'", $this->ShellEscape->escape(sprintf("'127.0.0.1 %s' `cat /tmp/123`'", BS)));
        $this->assertEquals("'127.0.0.1 \'\'' `cat /tmp/123`'", $this->ShellEscape->escape(sprintf("'127.0.0.1 %s%s' `cat /tmp/123`'", BS, BS)));
    }
}
