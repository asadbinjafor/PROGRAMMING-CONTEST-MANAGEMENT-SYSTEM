from __future__ import annotations

from pathlib import Path

from docx import Document
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Inches, Pt, RGBColor

from build_connection_video_guide import (
    BLACK,
    GRAY,
    GREEN,
    LAVENDER,
    PURPLE,
    PURPLE_DARK,
    WHITE,
    add_body,
    add_bullets,
    add_code,
    add_heading,
    add_numbered,
    add_page_break,
    add_page_number,
    add_table,
    configure_document,
    set_run_font,
)


ROOT = Path(r"E:\advance_database")
OUT = ROOT / "PCMS_Complete_Project_Run_Guide_Bangla.docx"


def add_hyperlink(paragraph, text: str, url: str):
    part = paragraph.part
    relationship_id = part.relate_to(
        url,
        "http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink",
        is_external=True,
    )
    hyperlink = OxmlElement("w:hyperlink")
    hyperlink.set(qn("r:id"), relationship_id)
    run = OxmlElement("w:r")
    run_properties = OxmlElement("w:rPr")
    color = OxmlElement("w:color")
    color.set(qn("w:val"), "0563C1")
    underline = OxmlElement("w:u")
    underline.set(qn("w:val"), "single")
    run_properties.append(color)
    run_properties.append(underline)
    run.append(run_properties)
    text_element = OxmlElement("w:t")
    text_element.text = text
    run.append(text_element)
    hyperlink.append(run)
    paragraph._p.append(hyperlink)
    return hyperlink


def add_link_line(doc, label: str, url: str, note: str = ""):
    p = doc.add_paragraph(style="Body Text")
    r = p.add_run("•  ")
    set_run_font(r, color=PURPLE, bold=True)
    add_hyperlink(p, label, url)
    if note:
        r = p.add_run(" — " + note)
        set_run_font(r, color=BLACK)
    return p


def add_gate(doc, title: str, expected: str):
    p = doc.add_paragraph(style="Body Text")
    p.paragraph_format.keep_together = True
    r = p.add_run("PASS GATE — " + title + ": ")
    set_run_font(r, bold=True, color=GREEN)
    r = p.add_run(expected)
    set_run_font(r, color=BLACK)


def add_warning(doc, title: str, text: str):
    p = doc.add_paragraph(style="Body Text")
    p.paragraph_format.keep_together = True
    r = p.add_run(title + " — ")
    set_run_font(r, bold=True, color="A61B1B")
    r = p.add_run(text)
    set_run_font(r, color=BLACK)


def set_footer(doc):
    for section in doc.sections:
        footer = section.footer
        p = footer.paragraphs[0] if footer.paragraphs else footer.add_paragraph()
        p.clear()
        p.alignment = WD_ALIGN_PARAGRAPH.RIGHT
        r = p.add_run("PCMS Complete Project Run Guide   •   ")
        set_run_font(r, size=8, color=GRAY)
        begin = OxmlElement("w:fldChar")
        begin.set(qn("w:fldCharType"), "begin")
        instr = OxmlElement("w:instrText")
        instr.set(qn("xml:space"), "preserve")
        instr.text = "PAGE"
        separate = OxmlElement("w:fldChar")
        separate.set(qn("w:fldCharType"), "separate")
        number = OxmlElement("w:t")
        number.text = "1"
        end = OxmlElement("w:fldChar")
        end.set(qn("w:fldCharType"), "end")
        r._r.append(begin)
        r._r.append(instr)
        r._r.append(separate)
        r._r.append(number)
        r._r.append(end)


def title_page(doc):
    for _ in range(2):
        doc.add_paragraph()
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("PCMS")
    set_run_font(r, size=30, bold=True, color=PURPLE_DARK)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_after = Pt(8)
    r = p.add_run("Complete Project Run Guide")
    set_run_font(r, size=23, bold=True, color=BLACK)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("Windows + PHP + OCI8 + Oracle Database")
    set_run_font(r, size=13, bold=True, color=PURPLE)

    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("শুরু থেকে সম্পূর্ণভাবে project run করার step-by-step বাংলা নির্দেশিকা")
    set_run_font(r, size=12, color=GRAY)

    doc.add_paragraph()
    add_table(doc, ["Document scope", "এই গাইডে যা আছে"], [
        ("প্রথমবার setup", "PHP, OCI8, Instant Client, Oracle Database, PATH ও php.ini"),
        ("Project setup", ".env, APP_KEY, PCMS_APP schema, seed data ও verification"),
        ("Run & test", "Development server, database test, 68 smoke assertions ও live HTTP checks"),
        ("Use & demo", "Admin, organizer ও participant login এবং মূল workflow"),
        ("Support", "Daily routine, reset, XAMPP/Apache option এবং troubleshooting"),
    ], widths=[1.65, 4.95])
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("Prepared for: E:\\advance_database")
    set_run_font(r, size=10, bold=True, color=PURPLE_DARK)
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("Verified workstation date: 18 September 2026")
    set_run_font(r, size=9, color=GRAY)
    add_page_break(doc)


def build():
    doc = Document()
    configure_document(doc)
    set_footer(doc)
    title_page(doc)

    add_heading(doc, "এই document কীভাবে ব্যবহার করবেন", 1)
    add_body(doc, "এই guide-টি দুই ধরনের অবস্থার জন্য লেখা: (A) বর্তমান computer-এ যেখানে PCMS ইতিমধ্যে configure করা আছে, এবং (B) একদম নতুন Windows computer-এ first-time setup। বর্তমান computer হলে Section 1-এর Fast Path অনুসরণ করলেই project চালু হবে। নতুন computer হলে Section 2 থেকে ধারাবাহিকভাবে কাজ করুন।")
    add_warning(doc, "সবচেয়ে গুরুত্বপূর্ণ", "কোনো step skip করবেন না। প্রতিটি PASS GATE সফল না হলে পরের section-এ যাবেন না। real password বা .env file screenshot/video/public repository-তে দেবেন না।")
    add_heading(doc, "Guide map", 2)
    add_table(doc, ["আপনার অবস্থা", "কোথা থেকে শুরু করবেন", "শেষ লক্ষ্য"], [
        ("এই বর্তমান PC", "Section 1 → 6 → 7 → 8", "কয়েক মিনিটে PCMS চালু ও verify"),
        ("নতুন Windows PC", "Section 2 থেকে সব section", "সব dependency install করে complete run"),
        ("Apache/XAMPP ব্যবহার করতে চান", "প্রথমে Section 2–7, তারপর Section 11", "public/ document root দিয়ে Apache run"),
        ("Error পাচ্ছেন", "Section 12-এর symptom অনুযায়ী", "কারণ identify ও fix"),
    ], widths=[1.7, 2.35, 2.55])
    add_heading(doc, "Project run flow", 2)
    add_body(doc, "Oracle services → PHP/OCI8 verification → .env verification → database scripts → automated tests → PHP server → browser login → role workflow check")
    add_heading(doc, "Document conventions", 2)
    add_bullets(doc, [
        "PowerShell command ধূসর code block-এ দেখানো হয়েছে।",
        "SQL> লেখা থাকলে command SQL*Plus-এর ভিতরে চালাতে হবে; PowerShell-এ নয়।",
        "<DB_PASSWORD> এবং <LONG_RANDOM_APP_KEY> placeholder নিজের secret দিয়ে বদলাবেন; angle brackets লিখে রাখবেন না।",
        "Terminal 1 server চালু রাখবে; Terminal 2 দিয়ে tests চালাবেন।",
        "Recommended path হলো PHP built-in development server। Apache/XAMPP optional।",
    ])

    add_page_break(doc)
    add_heading(doc, "1. বর্তমান computer-এ দ্রুত PCMS চালু করুন (Fast Path)", 1)
    add_body(doc, "এই workstation-এ project path, PHP, OCI8, Oracle XE, schema এবং test data already configured ও যাচাইকৃত। তাই প্রথমে এই section অনুসরণ করুন।")
    add_heading(doc, "1.1 বর্তমানে যাচাইকৃত configuration", 2)
    add_table(doc, ["Component", "Verified value"], [
        ("Project folder", r"E:\advance_database"),
        ("PHP", r"8.5.5 x64 NTS, C:\php\php.exe"),
        ("Active php.ini", r"C:\php\php.ini"),
        ("OCI8", "3.4.1, enabled"),
        ("Oracle client runtime", r"Instant Client 19.26, C:\Oracle\instantclient_19_26"),
        ("Oracle Database", "Local Oracle XE; service name XE; port 1521"),
        ("Oracle services", "OracleServiceXE এবং OracleXETNSListener — Running/Automatic"),
        ("Application URL", "http://127.0.0.1:8765"),
    ], widths=[2.05, 4.55])
    add_heading(doc, "1.2 Step 1 — Oracle services check", 2)
    add_code(doc, "Get-Service OracleServiceXE,OracleXETNSListener")
    add_body(doc, "দুটির Status যদি Running হয়, এগিয়ে যান। Stopped হলে Administrator PowerShell খুলে চালান:")
    add_code(doc, "Start-Service OracleServiceXE\nStart-Service OracleXETNSListener")
    add_gate(doc, "Oracle service", "OracleServiceXE এবং OracleXETNSListener—দুটিই Running।")

    add_heading(doc, "1.3 Step 2 — Project folder ও PHP check", 2)
    add_code(doc, "cd E:\\advance_database\nphp -v\nphp --ini\nphp --ri oci8")
    add_body(doc, "যদি 'php is not recognized' আসে, PATH ঠিক না হওয়া পর্যন্ত full path ব্যবহার করুন:")
    add_code(doc, "C:\\php\\php.exe -v\nC:\\php\\php.exe --ri oci8")
    add_gate(doc, "PHP/OCI8", "PHP 8.2+ দেখা যাবে এবং 'OCI8 Support => enabled' থাকবে।")

    add_heading(doc, "1.4 Step 3 — Database connection ও code tests", 2)
    add_code(doc, "php tests\\oracle_connection.php\nphp tests\\lint.php\nphp tests\\run.php")
    add_table(doc, ["Command", "Expected success evidence"], [
        ("oracle_connection.php", "OCI8 connection passed; PCMS users: 4; PCMS contests: 1"),
        ("lint.php", "PHP lint passed."),
        ("run.php", "All 68 smoke assertions passed."),
    ], widths=[2.25, 4.35])
    add_gate(doc, "Pre-run tests", "উপরের তিনটি command error ছাড়া pass।")

    add_heading(doc, "1.5 Step 4 — Server চালু করুন", 2)
    add_body(doc, "Terminal 1-এ project root থেকে নিচের command চালান এবং window বন্ধ করবেন না:")
    add_code(doc, "cd E:\\advance_database\nphp -S 127.0.0.1:8765 -t public public/router.php")
    add_body(doc, "Expected line: PHP Development Server (http://127.0.0.1:8765) started")
    add_body(doc, "Browser-এ খুলুন: http://127.0.0.1:8765")

    add_heading(doc, "1.6 Step 5 — Live HTTP verification", 2)
    add_body(doc, "Server চালু থাকা অবস্থায় নতুন Terminal 2 খুলে চালান:")
    add_code(doc, "cd E:\\advance_database\npowershell -ExecutionPolicy Bypass -File tests\\live_http.ps1")
    add_gate(doc, "HTTP tests", "13টি row-তেই Pass=True এবং শেষে 'All live HTTP checks passed.'")
    add_body(doc, "এখন Section 8-এর account দিয়ে login করে manual workflow check করুন। কাজ শেষে Terminal 1-এ Ctrl+C চাপলে server বন্ধ হবে।")

    add_page_break(doc)
    add_heading(doc, "2. নতুন Windows computer-এর জন্য প্রয়োজনীয় software", 1)
    add_body(doc, "এই section clean/new computer-এর জন্য। Windows 10/11 64-bit ধরে নির্দেশনা দেওয়া হয়েছে। 32-bit PHP, 64-bit OCI8 ও 64-bit Instant Client mix করবেন না। সব component একই architecture হতে হবে।")
    add_heading(doc, "2.1 বাধ্যতামূলক বনাম optional", 2)
    add_table(doc, ["Software", "Required?", "Purpose"], [
        ("PHP 8.2+ x64", "হ্যাঁ", "Application runtime এবং local web server"),
        ("OCI8 extension", "হ্যাঁ", "PHP থেকে Oracle connection"),
        ("Oracle Instant Client Basic x64", "হ্যাঁ", "Oracle client DLL/runtime"),
        ("Oracle Database XE/Free", "হ্যাঁ", "PCMS data, PL/SQL, views, constraints"),
        ("Microsoft VC++ Redistributable x64", "হ্যাঁ", "PHP/Oracle DLL runtime dependency"),
        ("SQL*Plus", "হ্যাঁ", "Database script install ও verification; XE-তে bundled হতে পারে"),
        ("Composer", "Optional", "Project fallback autoloader আছে; run করার জন্য বাধ্যতামূলক নয়"),
        ("XAMPP/Apache", "Optional", "Built-in server-এর বিকল্প; MariaDB ব্যবহার করবেন না"),
        ("Node.js/npm", "না", "বর্তমান PCMS run করতে দরকার নেই"),
    ], widths=[2.2, 1.05, 3.35])
    add_heading(doc, "2.2 Official download links", 2)
    add_link_line(doc, "PHP for Windows — official downloads", "https://windows.php.net/download/", "PHP 8.2+ x64 build নিন")
    add_link_line(doc, "PHP OCI8 installation manual", "https://www.php.net/manual/en/oci8.installation.php", "Windows OCI8 setup rules")
    add_link_line(doc, "PECL OCI8 package", "https://pecl.php.net/package/oci8", "exact PHP build-matching DLL")
    add_link_line(doc, "Oracle Instant Client for Windows x64", "https://www.oracle.com/database/technologies/instant-client/winx64-64-downloads.html", "Basic/Basic Light package")
    add_link_line(doc, "Oracle Database XE downloads", "https://www.oracle.com/database/technologies/xe-downloads.html", "local Oracle database")
    add_link_line(doc, "Oracle Database 21c XE for Windows", "https://www.oracle.com/uk/database/technologies/xe-downloads.html", "Windows x64 installer page")
    add_link_line(doc, "Microsoft Visual C++ Redistributable", "https://learn.microsoft.com/en-us/cpp/windows/latest-supported-vc-redist", "latest supported x64 runtime")
    add_link_line(doc, "Composer download", "https://getcomposer.org/download/", "optional dependency manager")
    add_link_line(doc, "XAMPP download", "https://www.apachefriends.org/download.html", "optional Apache bundle")
    add_warning(doc, "Download safety", "শুধু official links ব্যবহার করুন। third-party DLL site থেকে php_oci8 বা oci.dll download করবেন না।")

    add_heading(doc, "2.3 Matching rule—সবচেয়ে common failure এখানেই", 2)
    add_code(doc, "php -i | findstr /I \"Architecture Thread Safety Compiler\"")
    add_table(doc, ["php -i output", "যা download করবেন"], [
        ("Architecture = x64", "x64 OCI8 DLL + x64 Instant Client"),
        ("Thread Safety = disabled / NTS", "NTS OCI8 package"),
        ("Thread Safety = enabled / TS", "TS OCI8 package; Apache module setups often TS"),
        ("Compiler = Visual C++ 2022 / VS17", "VS17-compatible OCI8 DLL এবং VC++ x64 runtime"),
        ("PHP = 8.x", "একই PHP minor/API-এর package; filename/README check করুন"),
    ], widths=[2.9, 3.7])
    add_warning(doc, "Rule", "x86/x64, TS/NTS, PHP version/API বা Visual C++ build mismatch হলে সাধারণত 'Unable to load dynamic library' দেখা যায়।")

    add_page_break(doc)
    add_heading(doc, "3. PHP এবং OCI8 install — clean PC", 1)
    add_heading(doc, "3.1 PHP install", 2)
    add_numbered(doc, [
        "PHP official Windows page থেকে PHP 8.2 বা newer x64 ZIP download করুন। Built-in server path-এর জন্য NTS build সহজ।",
        r"ZIP extract করে C:\php folder বানান। final path যেন C:\php\php.exe হয়; extra nested folder না থাকে।",
        r"C:\php\php.ini-development copy করে C:\php\php.ini নাম দিন।",
        r"Windows Search → 'Edit the system environment variables' → Environment Variables → System Path → New → C:\php যোগ করুন।",
        "সব পুরোনো PowerShell/Command Prompt বন্ধ করে নতুন terminal খুলুন।",
    ])
    add_code(doc, "where.exe php\nphp -v\nphp --ini")
    add_gate(doc, "PHP install", r"where.exe php-তে C:\php\php.exe এবং php --ini-তে C:\php\php.ini।")

    add_heading(doc, "3.2 Visual C++ runtime install", 2)
    add_numbered(doc, [
        "Microsoft-এর official link থেকে latest supported Visual C++ Redistributable x64 download করুন।",
        "Installer Run as administrator → Install/Repair → প্রয়োজন হলে restart।",
        "64-bit PHP ব্যবহার করলে অন্তত x64 package বাধ্যতামূলক।",
    ])

    add_heading(doc, "3.3 Oracle Instant Client install", 2)
    add_numbered(doc, [
        "Oracle Instant Client Windows x64 page থেকে Basic অথবা Basic Light package download করুন।",
        r"ZIP extract করে একটি স্থায়ী path দিন, যেমন C:\Oracle\instantclient_19_26 বা downloaded version অনুযায়ী folder।",
        "Folder-এর ভিতরে oci.dll আছে কি না check করুন।",
        "System Path-এ ওই Instant Client folder যোগ করুন। Multiple Oracle client থাকলে intended Instant Client path পুরোনো client-এর আগে রাখুন।",
        "নতুন terminal খুলুন; running Apache/PHP process থাকলে restart করুন।",
    ])
    add_code(doc, "where.exe oci.dll\nGet-ChildItem C:\\Oracle\\instantclient_*\\oci.dll")
    add_body(doc, "প্রথম command কিছু না দেখালে Path process-এ refresh হয়নি বা folder ভুল। Windows sign-out/restart দরকার হতে পারে।")

    add_heading(doc, "3.4 OCI8 DLL install", 2)
    add_numbered(doc, [
        "আগের matching command-এর PHP version, x64, NTS/TS এবং compiler তথ্য লিখে রাখুন।",
        "PECL OCI8 page থেকে ঠিক matching Windows DLL package download করুন।",
        r"Package-এর php_oci8_*.dll file C:\php\ext-এ copy করুন।",
        r"এই project-এর verified configuration php_oci8_19.dll নাম ব্যবহার করে; আপনার downloaded package-এ যে exact filename আছে, php.ini-তে সেটিই লিখবেন।",
        r"C:\php\php.ini খুলে extension_dir এবং OCI8 line ঠিক করুন। একই সময়ে একটির বেশি OCI8 DLL enable করবেন না।",
    ])
    add_code(doc, '[PHP]\nextension_dir = "C:\\php\\ext"\nextension = php_oci8_19.dll\ndate.timezone = Asia/Dhaka\ndisplay_errors = On\ndisplay_startup_errors = On\nlog_errors = On\nerror_reporting = E_ALL\ndefault_charset = "UTF-8"\nsession.use_strict_mode = 1\nsession.cookie_httponly = 1\nsession.cookie_samesite = Lax')
    add_body(doc, "Project reference file: E:\\advance_database\\config\\php.ini.example")
    add_code(doc, "php -m | findstr /I oci8\nphp --ri oci8\nphp -r \"var_dump(extension_loaded('oci8'));\"")
    add_gate(doc, "OCI8 install", "oci8 module listed, OCI8 Support enabled, এবং boolean true।")
    add_warning(doc, "যদি কাজ না করে", "DLL file বারবার বদলানোর আগে Section 12.1-এর matching ও dependency checklist সম্পূর্ণ করুন।")

    add_heading(doc, "4. Oracle Database install ও PCMS schema তৈরি", 1)
    add_heading(doc, "4.1 Existing workstation বনাম clean installation", 2)
    add_table(doc, ["Situation", "Service setting"], [
        ("এই বর্তমান workstation (verified Oracle XE 10g)", "DB_SERVICE=XE"),
        ("Oracle 21c XE fresh install", "সাধারণত pluggable service XEPDB1; listener output দিয়ে নিশ্চিত করুন"),
        ("অন্য Oracle server", "DBA-provided service name/SID ব্যবহার করুন; অনুমান করবেন না"),
    ], widths=[3.25, 3.35])
    add_warning(doc, "Production note", "বর্তমান Oracle XE 10g শুধু local academic environment-এ চলছে; production-এর জন্য supported modern Oracle release ব্যবহার করুন।")

    add_heading(doc, "4.2 Oracle XE install — clean PC", 2)
    add_numbered(doc, [
        "Oracle XE/Free official page থেকে supported Windows x64 installer download করুন।",
        "ZIP extract করে setup.exe Run as administrator করুন।",
        "Installation path, listener port (normally 1521), database password এবং service details নিরাপদে লিখে রাখুন।",
        "Installation শেষে Windows Services-এ Oracle database service ও TNS Listener Running করুন।",
        "SQL*Plus খুলে SYSTEM account দিয়ে intended service-এ connect করুন।",
    ])
    add_code(doc, "Get-Service | Where-Object { $_.Name -match 'Oracle|TNS' }\nnetstat -ano | findstr :1521\nlsnrctl status")
    add_body(doc, "21c XE example (নিজের SYSTEM password prompt-এ দিন):")
    add_code(doc, "sqlplus system@//127.0.0.1:1521/XEPDB1")
    add_body(doc, "বর্তমান workstation-এ SQL*Plus executable প্রয়োজনে full path দিয়ে চালাতে পারেন:")
    add_code(doc, "C:\\oraclexe\\app\\oracle\\product\\10.2.0\\server\\BIN\\sqlplus.exe")

    add_heading(doc, "4.3 Dedicated PCMS_APP schema তৈরি", 2)
    add_body(doc, "SYS বা SYSTEM schema-র মধ্যে application tables তৈরি করবেন না। DBA/SYSTEM connection-এর SQL> prompt-এ চালান:")
    add_code(doc, 'CREATE USER PCMS_APP IDENTIFIED BY "<strong-random-password>";\nGRANT CREATE SESSION, CREATE TABLE, CREATE VIEW, CREATE SEQUENCE,\n      CREATE PROCEDURE, CREATE TRIGGER TO PCMS_APP;\nALTER USER PCMS_APP QUOTA UNLIMITED ON USERS;')
    add_body(doc, "Oracle password policy angle bracket গ্রহণ না করলে placeholder বাদ দিয়ে নিজের strong password দিন। সেই একই password পরে .env-এ থাকবে।")
    add_gate(doc, "Schema account", "PCMS_APP user তৈরি এবং CREATE SESSION সহ প্রয়োজনীয় grants পাওয়া গেছে।")

    add_heading(doc, "4.4 Database scripts install", 2)
    add_body(doc, "PowerShell-এ database folder-এ যান এবং PCMS_APP দিয়ে connect করুন। বর্তমান workstation-এর exact command:")
    add_code(doc, "cd E:\\advance_database\\database\nsqlplus PCMS_APP@//127.0.0.1:1521/XE")
    add_body(doc, "Fresh 21c XE হলে service XEPDB1 হতে পারে:")
    add_code(doc, "sqlplus PCMS_APP@//127.0.0.1:1521/XEPDB1")
    add_body(doc, "Password prompt-এ schema password দিন। তারপর SQL> prompt-এ:")
    add_code(doc, "@install.sql")
    add_body(doc, "install.sql ধারাবাহিকভাবে schema, constraints/indexes, canonical seed, PL/SQL units, positive verification এবং negative tests চালায়। SQLERROR হলে script stop করবে।")
    add_table(doc, ["Script", "Purpose"], [
        ("01_schema.sql", "Tables, sequences ও base objects"),
        ("02_constraints_indexes.sql", "Keys, checks, uniqueness ও indexes"),
        ("03_seed.sql", "2026 canonical demo accounts/contest data"),
        ("04_program_units.sql", "PL/SQL procedures/functions/triggers/views"),
        ("05_verify.sql", "Invalid objects, USER_ERRORS, counts ও behavior checks"),
        ("06_negative_tests.sql", "Invalid operations correctly rejected কি না"),
    ], widths=[2.2, 4.4])
    add_gate(doc, "Database install", "কোনো unhandled ORA error নেই; invalid object ও USER_ERRORS শূন্য।")

    add_page_break(doc)
    add_heading(doc, "5. Project configuration (.env)", 1)
    add_heading(doc, "5.1 .env তৈরি বা সংরক্ষণ", 2)
    add_body(doc, "Project root-এ .env আগে না থাকলে শুধু তখন copy করুন:")
    add_code(doc, "cd E:\\advance_database\nif (-not (Test-Path .env)) { Copy-Item .env.example .env }")
    add_warning(doc, "Overwrite করবেন না", "Existing .env-এ working credential থাকতে পারে। Copy-Item -Force বা public commit করবেন না।")
    add_body(doc, "APP_KEY generate করতে:")
    add_code(doc, "php -r \"echo bin2hex(random_bytes(32)), PHP_EOL;\"")
    add_body(doc, "Generated value copy করে APP_KEY-এ দিন। Example structure:")
    add_code(doc, "APP_ENV=local\nAPP_DEBUG=true\nAPP_URL=http://127.0.0.1:8765\nAPP_TIMEZONE=Asia/Dhaka\nAPP_KEY=<LONG_RANDOM_APP_KEY>\n\nDB_CONNECTION=oci8\nDB_HOST=127.0.0.1\nDB_PORT=1521\nDB_SERVICE=XE\nDB_USERNAME=PCMS_APP\nDB_PASSWORD=<DB_PASSWORD>\nDB_CHARSET=AL32UTF8\n\nSESSION_NAME=pcms_session\nSESSION_SECURE=false\nREMEMBER_DAYS=30")
    add_heading(doc, "5.2 কোন value কখন বদলাবেন", 2)
    add_table(doc, ["Key", "Local value", "কখন বদলাবেন"], [
        ("APP_ENV", "local", "Production-এ production"),
        ("APP_DEBUG", "true", "Public/production-এ false"),
        ("APP_URL", "http://127.0.0.1:8765", "Domain/port বদলালে"),
        ("DB_HOST", "127.0.0.1", "Database অন্য host-এ হলে"),
        ("DB_PORT", "1521", "Listener অন্য port-এ হলে"),
        ("DB_SERVICE", "XE", "21c XE হলে XEPDB1 হতে পারে; listener/DBA অনুযায়ী"),
        ("DB_USERNAME", "PCMS_APP", "Dedicated schema name বদলালে"),
        ("DB_PASSWORD", "secret", "Schema create/change password-এর exact value"),
        ("SESSION_SECURE", "false", "HTTPS production-এ true"),
    ], widths=[1.45, 2.05, 3.1])
    add_body(doc, "File save করার পর terminal/server restart করুন, কারণ running process পুরোনো environment/config ধরে রাখতে পারে।")

    add_heading(doc, "5.3 Composer (optional)", 2)
    add_body(doc, "বর্তমান project app/autoload.php fallback ব্যবহার করে; তাই Composer install না থাকলেও application run হয়। Composer install করলে project root-এ নিচের command optional:")
    add_code(doc, "composer install\ncomposer dump-autoload")
    add_warning(doc, "Composer error", "composer.json ext-oci8 require করে। OCI8 pass করার আগে composer install চালালে dependency check fail হওয়া স্বাভাবিক।")

    add_heading(doc, "6. Run-এর আগে complete verification", 1)
    add_body(doc, "এই section হলো go/no-go checklist। নিচের order বজায় রাখুন।")
    add_heading(doc, "6.1 Tool identity", 2)
    add_code(doc, "cd E:\\advance_database\nwhere.exe php\nphp -v\nphp --ini\nphp --ri oci8\nwhere.exe oci.dll")
    add_gate(doc, "Runtime identity", "Intended php.exe, intended php.ini, OCI8 enabled, intended Oracle client।")

    add_heading(doc, "6.2 Oracle reachability", 2)
    add_code(doc, "Get-Service OracleServiceXE,OracleXETNSListener\nnetstat -ano | findstr :1521\nphp tests\\oracle_connection.php")
    add_body(doc, "Verified sample output:")
    add_code(doc, "OCI8 connection passed.\nDatabase: Oracle Database 10g Express Edition Release 10.2.0.1.0 - Product\nPCMS users: 4\nPCMS contests: 1")
    add_gate(doc, "PHP-to-Oracle", "Connection passed এবং seed count পাওয়া গেছে।")

    add_heading(doc, "6.3 Syntax ও application smoke tests", 2)
    add_code(doc, "php tests\\lint.php\nphp tests\\run.php")
    add_body(doc, "Expected:")
    add_code(doc, "PHP lint passed.\nAll 68 smoke assertions passed.")
    add_gate(doc, "Code tests", "দুটিই exact success message দেখায়।")

    add_heading(doc, "6.4 Database verification পুনরায় চালাতে চাইলে", 2)
    add_code(doc, "cd E:\\advance_database\nsqlplus PCMS_APP@//127.0.0.1:1521/XE")
    add_body(doc, "তারপর SQL> prompt-এ:")
    add_code(doc, "@database/05_verify.sql")
    add_gate(doc, "Oracle objects", "Invalid objects এবং USER_ERRORS নেই।")

    add_page_break(doc)
    add_heading(doc, "7. Web server চালু ও end-to-end test", 1)
    add_heading(doc, "7.1 Terminal 1 — development server", 2)
    add_code(doc, "cd E:\\advance_database\nphp -S 127.0.0.1:8765 -t public public/router.php")
    add_bullets(doc, [
        "Command চালুর পর terminal open রাখুন।",
        "public/ document root ব্যবহার করা security ও routing-এর জন্য জরুরি। project root-কে document root করবেন না।",
        "Port busy হলে Section 12.5 অনুসরণ করুন।",
        "বন্ধ করতে Ctrl+C।",
    ])
    add_heading(doc, "7.2 Browser check", 2)
    add_numbered(doc, [
        "Browser address bar-এ http://127.0.0.1:8765 লিখুন।",
        "Home page load হয় কি না দেখুন।",
        "Contests, Leaderboard এবং Announcements navigation খুলুন।",
        "Login page খুলুন; এরপর Section 8-এর account ব্যবহার করুন।",
    ])
    add_heading(doc, "7.3 Terminal 2 — live HTTP test", 2)
    add_code(doc, "cd E:\\advance_database\npowershell -ExecutionPolicy Bypass -File tests\\live_http.ps1")
    add_body(doc, "Script public pages, তিন role-এর dashboard/page এবং representative 403 authorization boundary পরীক্ষা করে।")
    add_gate(doc, "End-to-end HTTP", "সব row Pass=True; final line 'All live HTTP checks passed.'")
    add_warning(doc, "Execution policy", "System-wide policy পরিবর্তন করবেন না। command-এর -ExecutionPolicy Bypass শুধু এই process-এর জন্য।")

    add_heading(doc, "7.4 সফল run-এর definition", 2)
    add_table(doc, ["Layer", "Success evidence"], [
        ("Oracle", "Services running; port 1521; schema scripts verified"),
        ("PHP", "8.2+; correct php.ini; OCI8 enabled"),
        ("Application", "lint এবং 68 smoke assertions pass"),
        ("HTTP", "Public + role pages return expected 200/403"),
        ("Browser", "CSS/JS loaded; login, CRUD actions ও logout work"),
    ], widths=[1.45, 5.15])

    add_page_break(doc)
    add_heading(doc, "8. Login accounts ও role-based manual workflow", 1)
    add_warning(doc, "Demo data only", "সব account synthetic। Public deployment-এর আগে demo password/account change বা remove করুন।")
    add_table(doc, ["Role", "Email", "Demo password"], [
        ("Administrator", "admin@pcms.test", "Password123!"),
        ("Organizer", "organizer@pcms.test", "Password123!"),
        ("Participant", "participant@pcms.test", "Password123!"),
        ("Participant", "shishir@pcms.test", "Password123!"),
    ], widths=[1.55, 3.0, 2.05])

    add_heading(doc, "8.1 Participant workflow", 2)
    add_numbered(doc, [
        "participant@pcms.test দিয়ে login করুন।",
        "Approved contest browse করুন এবং contest detail খুলুন।",
        "Registration status অনুযায়ী Register অথবা Cancel Registration test করুন।",
        "Problem list ও problem detail খুলুন।",
        "Source submission করুন; submission PENDING হিসেবে save হবে।",
        "Submission history, leaderboard, announcements এবং clarification question test করুন।",
        "Profile update, password change (প্রয়োজনে পরে seed password restore/reset) এবং logout check করুন।",
    ])
    add_body(doc, "Expected: participant organizer/admin page খুলতে পারবে না; unauthorized route-এ 403 হবে।")

    add_heading(doc, "8.2 Organizer workflow", 2)
    add_numbered(doc, [
        "organizer@pcms.test দিয়ে login করুন।",
        "Own contest list খুলুন; নতুন contest create/edit করুন।",
        "Problem এবং hidden test case CRUD check করুন।",
        "Announcement create/edit/delete এবং participant list দেখুন।",
        "Clarification answer করুন।",
        "Participant submission manual review করে verdict record করুন।",
        "অন্য organizer-এর owned resource modify করা blocked কি না check করুন।",
    ])
    add_body(doc, "Security boundary: PHP participant source code execute করে না। Automatic judge যোগ করতে হলে আলাদা isolated service দরকার।")

    add_heading(doc, "8.3 Administrator workflow", 2)
    add_numbered(doc, [
        "admin@pcms.test দিয়ে login করুন।",
        "User list, role assignment এবং access state পরীক্ষা করুন।",
        "Pending contest approve/reject flow test করুন।",
        "Monitoring/summary এবং audit log খুলুন।",
        "Participant/organizer restricted actions admin oversight অনুযায়ী কাজ করছে কি না দেখুন।",
        "Logout করুন এবং protected page আবার খুললে login redirect/denial হয় কি না verify করুন।",
    ])

    add_heading(doc, "8.4 CRUD test discipline", 2)
    add_bullets(doc, [
        "Create: unique demo title ব্যবহার করুন, যেমন 'Run Guide Test Contest'।",
        "Read: list এবং detail page-এ saved value মিলান।",
        "Update: title/description বদলে refresh-এর পর persist হয়েছে কি না দেখুন।",
        "Delete: শুধু নিজের disposable test record delete করুন; canonical seed record নয়।",
        "Validation: empty/invalid input submit করে error message এবং data preservation check করুন।",
        "Authorization: wrong role দিয়ে URL manually open করে 403 check করুন।",
    ])

    add_heading(doc, "9. প্রতিদিন project চালানোর short routine", 1)
    add_body(doc, "First-time setup শেষ হলে প্রতিদিন এই sequence যথেষ্ট।")
    add_numbered(doc, [
        "Oracle services Running কি না দেখুন।",
        r"PowerShell খুলে cd E:\advance_database করুন।",
        "একবার php tests/oracle_connection.php চালিয়ে DB connection check করুন।",
        "php -S 127.0.0.1:8765 -t public public/router.php চালান।",
        "Browser-এ http://127.0.0.1:8765 খুলুন।",
        "Major demo/changes-এর আগে php tests/lint.php এবং php tests/run.php চালান।",
        "শেষে server terminal-এ Ctrl+C চাপুন।",
    ])
    add_heading(doc, "Copy-paste daily commands", 2)
    add_code(doc, "Get-Service OracleServiceXE,OracleXETNSListener\ncd E:\\advance_database\nphp tests\\oracle_connection.php\nphp tests\\lint.php\nphp tests\\run.php\nphp -S 127.0.0.1:8765 -t public public/router.php")
    add_heading(doc, "Full demo-এর আগে", 2)
    add_code(doc, "# Terminal 2, server চলমান অবস্থায়\ncd E:\\advance_database\npowershell -ExecutionPolicy Bypass -File tests\\live_http.ps1")

    add_heading(doc, "10. Stop, restart, data reset ও recovery", 1)
    add_heading(doc, "10.1 Server stop/restart", 2)
    add_bullets(doc, [
        "Server stop: Terminal 1 active করে Ctrl+C।",
        "Config/PATH/php.ini change-এর পর: সব PHP/Apache process stop করে নতুন terminal থেকে server চালান।",
        "Oracle service restart প্রয়োজন হলে Administrator PowerShell ব্যবহার করুন।",
    ])
    add_code(doc, "Restart-Service OracleServiceXE\nRestart-Service OracleXETNSListener")
    add_warning(doc, "Active transaction", "Database service restart করার আগে অন্য user/session কাজ করছে কি না নিশ্চিত করুন।")

    add_heading(doc, "10.2 Database reset — destructive", 2)
    add_warning(doc, "Data loss", "reset.sql PCMS objects/data drop করতে পারে। প্রয়োজনীয় data export/backup ছাড়া চালাবেন না। এটি শুধু dedicated PCMS_APP schema ও local demo reset-এর জন্য।")
    add_code(doc, "cd E:\\advance_database\\database\nsqlplus PCMS_APP@//127.0.0.1:1521/XE")
    add_body(doc, "SQL> prompt-এ reset script review করে:")
    add_code(doc, "@reset.sql\n@install.sql")
    add_body(doc, "তারপর PowerShell-এ আবার তিন test চালান:")
    add_code(doc, "cd E:\\advance_database\nphp tests\\oracle_connection.php\nphp tests\\lint.php\nphp tests\\run.php")

    add_heading(doc, "10.3 Password change হলে", 2)
    add_numbered(doc, [
        "Oracle-এ PCMS_APP password পরিবর্তন করুন।",
        ".env-এর DB_PASSWORD একই নতুন value করুন।",
        "Server restart করুন।",
        "oracle_connection.php test pass করান।",
    ])

    add_heading(doc, "11. Apache/XAMPP দিয়ে চালাতে চাইলে (Optional)", 1)
    add_body(doc, "এই project run করার জন্য XAMPP দরকার নেই। Built-in server সবচেয়ে কম configuration-এ verified। Apache ব্যবহার করলে Oracle/OCI8 settings Apache-এর PHP build-এর সঙ্গে আলাদা করে match করতে হবে।")
    add_heading(doc, "11.1 গুরুত্বপূর্ণ পার্থক্য", 2)
    add_table(doc, ["Built-in server", "Apache/XAMPP"], [
        ("php command যে php.ini দেখায় সেটি ব্যবহার করে", "Apache অন্য php.ini/PHP binary ব্যবহার করতে পারে"),
        ("NTS build স্বাভাবিক", "Apache module সাধারণত TS build চায়"),
        ("Command-এর terminal PATH পায়", "Apache service restart না করলে নতুন System Path নাও পেতে পারে"),
        ("-t public দিয়ে document root explicit", "VirtualHost DocumentRoot অবশ্যই project/public"),
    ], widths=[3.3, 3.3])
    add_heading(doc, "11.2 Apache checklist", 2)
    add_numbered(doc, [
        "Apache/XAMPP-এর PHP version 8.2+ কি না check করুন।",
        "Apache PHP-এর architecture, TS/NTS এবং compiler-এর matching OCI8 DLL install করুন।",
        "Apache যে php.ini load করে সেখানে extension_dir ও OCI8 enable করুন।",
        r"VirtualHost DocumentRoot E:\advance_database\public করুন। project root expose করবেন না।",
        "mod_rewrite ও mod_headers enable করুন এবং public/.htaccess override allow করুন।",
        "Instant Client System Path set করার পর Apache service restart করুন।",
        "Apache error log-এ OCI8 loading error নেই কি না check করুন।",
        "Browser URL ও APP_URL মিলিয়ে দিন; তারপর live HTTP tests run করুন।",
    ])
    add_body(doc, "MariaDB/MySQL start করার প্রয়োজন নেই; PCMS Oracle ব্যবহার করে। XAMPP phpMyAdmin এই project-এর database manage করবে না।")

    add_page_break(doc)
    add_heading(doc, "12. Troubleshooting — symptom থেকে fix", 1)
    add_heading(doc, "12.1 OCI8 load হচ্ছে না", 2)
    add_table(doc, ["Symptom", "Check/Fix"], [
        ("Unable to load dynamic library php_oci8…", "Exact DLL filename, PHP version/API, x64, TS/NTS, VS compiler match করুন"),
        ("The specified module could not be found", "DLL present হলেও dependency missing হতে পারে; VC++ x64 ও Instant Client PATH check করুন"),
        ("php -m-এ oci8 নেই", "php --ini দিয়ে active php.ini শনাক্ত; wrong file edit করেছেন কি না দেখুন"),
        ("CLI works, Apache fails", "Apache-এর php.ini/build আলাদা; Apache restart এবং service PATH check করুন"),
        ("oci.dll wrong location", "where.exe oci.dll; intended Instant Client path আগে রাখুন"),
    ], widths=[2.45, 4.15])
    add_code(doc, "where.exe php\nphp --ini\nphp -i | findstr /I \"Architecture Thread Safety Compiler extension_dir\"\nwhere.exe oci.dll\nphp --ri oci8")

    add_heading(doc, "12.2 ORA-12541: no listener", 2)
    add_bullets(doc, [
        "Oracle listener service start করুন।",
        "Port 1521 listen করছে কি না netstat দিয়ে দেখুন।",
        "DB_HOST/DB_PORT .env-এর সঙ্গে listener match করুন।",
    ])
    add_code(doc, "Get-Service | Where-Object { $_.Name -match 'Oracle|TNS' }\nlsnrctl status\nnetstat -ano | findstr :1521")

    add_heading(doc, "12.3 ORA-12514 / service not known", 2)
    add_bullets(doc, [
        "DB_SERVICE ভুল। এই PC-তে XE; 21c XE-তে সাধারণত XEPDB1 হতে পারে।",
        "lsnrctl status-এর Services Summary থেকে exact service copy করুন।",
        "SQL*Plus Easy Connect দিয়ে একই host/port/service test করুন।",
    ])
    add_code(doc, "sqlplus PCMS_APP@//127.0.0.1:1521/XE")

    add_heading(doc, "12.4 ORA-01017 / invalid username-password", 2)
    add_bullets(doc, [
        "PCMS_APP এবং DB_PASSWORD exact কি না check করুন।",
        "Password-এ special character থাকলে .env parser format অনুযায়ী safe quoting প্রয়োজন হতে পারে।",
        "Account locked/expired হলে DBA দিয়ে unlock/reset করুন; production secret log করবেন না।",
    ])

    add_heading(doc, "12.5 Port 8765 already in use", 2)
    add_code(doc, "Get-NetTCPConnection -LocalPort 8765 -State Listen | Select-Object LocalAddress,LocalPort,OwningProcess\nGet-Process -Id <OwningProcess>")
    add_body(doc, "আপনার নিজের পুরোনো PHP server হলে সেটি Ctrl+C দিয়ে stop করুন। অন্য application হলে নতুন port ব্যবহার করুন:")
    add_code(doc, "php -S 127.0.0.1:8766 -t public public/router.php")
    add_body(doc, ".env-এ APP_URL=http://127.0.0.1:8766 করুন এবং নতুন URL browser-এ খুলুন।")

    add_heading(doc, "12.6 Browser 500 error / blank page", 2)
    add_numbered(doc, [
        "Server terminal-এর latest error পড়ুন।",
        "local environment-এ APP_DEBUG=true আছে কি না check করুন; production-এ কখনো true নয়।",
        "php tests/oracle_connection.php চালিয়ে DB আলাদা করে verify করুন।",
        "php tests/lint.php চালিয়ে syntax error ধরুন।",
        ".env key spelling, APP_KEY, DB_SERVICE ও DB_PASSWORD check করুন।",
        "Server restart করে আবার request দিন।",
    ])

    add_heading(doc, "12.7 CSS/JS load হচ্ছে না বা routes 404", 2)
    add_bullets(doc, [
        "Command project root থেকে চালিয়েছেন কি না check করুন।",
        "-t public এবং public/router.php দুটোই command-এ আছে কি না দেখুন।",
        "Apache হলে DocumentRoot public/ এবং rewrite enabled কি না check করুন।",
        "APP_URL current port/domain-এর সঙ্গে match করুন।",
    ])

    add_heading(doc, "12.8 Database install error", 2)
    add_table(doc, ["Error pattern", "Likely action"], [
        ("ORA-01920 / user already exists", "Existing PCMS_APP inspect করুন; blindly recreate নয়"),
        ("ORA-00955 / name already used", "Schema already partially installed; 05_verify চালান, দরকারে reviewed reset"),
        ("ORA-01031 / insufficient privileges", "Dedicated schema grants DBA দিয়ে ঠিক করুন"),
        ("Invalid object / USER_ERRORS", "SQL*Plus-এ SHOW ERRORS; 04_program_units.sql error line fix/inspect"),
        ("Seed duplicate constraint", "Existing data আছে; reset ছাড়া seed পুনরায় চালাবেন না"),
    ], widths=[2.55, 4.05])

    add_heading(doc, "12.9 Quick diagnosis order", 2)
    add_numbered(doc, [
        "PHP identity",
        "OCI8 loaded",
        "Oracle services/listener",
        "SQL*Plus login",
        ".env service/credential",
        "oracle_connection.php",
        "lint + smoke",
        "server + live HTTP",
        "browser role workflow",
    ])

    add_heading(doc, "13. Demo/video/class presentation-এর আগে checklist", 1)
    add_table(doc, ["✓", "Check"], [
        ("□", "Oracle services Running এবং network stable"),
        ("□", "php --ri oci8 successful"),
        ("□", "oracle_connection.php successful; users=4, contests=1"),
        ("□", "PHP lint passed"),
        ("□", "All 68 smoke assertions passed"),
        ("□", "All live HTTP checks passed"),
        ("□", "Admin, organizer, participant login আগে test করা"),
        ("□", "Canonical 2026 demo data intact"),
        ("□", "Browser zoom 100%, notifications off, unrelated tabs closed"),
        ("□", ".env, password, APP_KEY ও personal data screen-এ নেই"),
        ("□", "Disposable CRUD test record প্রস্তুত; important seed delete করা হবে না"),
        ("□", "Server terminal open; backup plan হিসেবে exact commands copy করা"),
    ], widths=[0.45, 6.15])
    add_heading(doc, "Suggested demo order", 2)
    add_numbered(doc, [
        "Home/contests/leaderboard public pages",
        "Participant login → registration → problem → submission → clarification",
        "Organizer login → contest/problem/test/announcement CRUD → manual verdict",
        "Admin login → user/role → contest approval → audit log",
        "Wrong-role 403 evidence",
        "Automated tests evidence",
    ])

    add_heading(doc, "14. Production-ready করার আগে বাধ্যতামূলক কাজ", 1)
    add_body(doc, "এই guide local/academic run complete করে। Internet-facing production deployment আলাদা security/deployment phase।")
    add_bullets(doc, [
        "Supported modern Oracle version এবং tested backup/restore plan।",
        "HTTPS/TLS; APP_ENV=production; APP_DEBUG=false; SESSION_SECURE=true।",
        "Strong unique database password, rotated APP_KEY/secrets এবং least-privilege database account।",
        "Demo account/password remove বা change।",
        "Persistent/shared login throttling, monitoring এবং centralized logs।",
        "Real password-reset email adapter এবং mail delivery monitoring।",
        "Automatic judge দরকার হলে web/database host থেকে আলাদা sandboxed execution service।",
        "Regular patching of PHP, OCI8, Instant Client, Oracle এবং web server।",
    ])
    add_warning(doc, "Never", "PHP exec, shell_exec, system বা backticks দিয়ে participant code web/database host-এ execute করবেন না।")

    add_heading(doc, "Appendix A — Complete command sheet", 1)
    add_heading(doc, "A.1 Environment identity", 2)
    add_code(doc, "cd E:\\advance_database\nwhere.exe php\nphp -v\nphp --ini\nphp -i | findstr /I \"Architecture Thread Safety Compiler extension_dir\"\nphp --ri oci8\nwhere.exe oci.dll")
    add_heading(doc, "A.2 Oracle", 2)
    add_code(doc, "Get-Service OracleServiceXE,OracleXETNSListener\nnetstat -ano | findstr :1521\nlsnrctl status\nsqlplus PCMS_APP@//127.0.0.1:1521/XE")
    add_heading(doc, "A.3 Application tests", 2)
    add_code(doc, "php tests\\oracle_connection.php\nphp tests\\lint.php\nphp tests\\run.php")
    add_heading(doc, "A.4 Run and live test", 2)
    add_code(doc, "# Terminal 1\nphp -S 127.0.0.1:8765 -t public public/router.php\n\n# Terminal 2\npowershell -ExecutionPolicy Bypass -File tests\\live_http.ps1")
    add_heading(doc, "A.5 Stop", 2)
    add_code(doc, "# Terminal 1-এ\nCtrl+C")

    add_heading(doc, "Appendix B — Final acceptance checklist", 1)
    add_table(doc, ["✓", "Acceptance condition"], [
        ("□", "Software official source থেকে installed"),
        ("□", "PHP/OCI8/Instant Client architecture এবং build matched"),
        ("□", "Oracle service/listener running"),
        ("□", "PCMS_APP dedicated schema installed"),
        ("□", ".env secret-safe এবং correct service/credential configured"),
        ("□", "Oracle verification has no invalid object/USER_ERRORS"),
        ("□", "Connection, lint, 68 smoke ও live HTTP tests pass"),
        ("□", "Browser assets/navigation work"),
        ("□", "Participant workflow complete"),
        ("□", "Organizer CRUD/manual review complete"),
        ("□", "Admin user/approval/audit workflow complete"),
        ("□", "Wrong-role access returns 403"),
        ("□", "Server cleanly stops with Ctrl+C"),
    ], widths=[0.45, 6.15])
    add_body(doc, "সব box complete হলে PCMS local development/demo environment সফলভাবে runnable।")

    add_heading(doc, "Appendix C — Project file map", 1)
    add_table(doc, ["Path", "Purpose"], [
        (r"public\index.php; public\router.php; public\.htaccess", "Front controller এবং PHP/Apache routing"),
        (r"routes\web.php", "Application route table"),
        (r"app\Support\Database.php", "OCI8 adapter and named binds"),
        (r".env.example; config\php.ini.example", "Safe environment ও PHP/OCI8 templates"),
        (r"database\install.sql; database\05_verify.sql", "Ordered Oracle install এবং validation"),
        (r"tests\oracle_connection.php; tests\lint.php", "Real DB connectivity/seed ও syntax checks"),
        (r"tests\run.php; tests\live_http.ps1", "68 smoke assertions এবং role-aware HTTP tests"),
        (r"README.md", "Project summary and quick setup"),
    ], widths=[3.35, 3.25])
    add_body(doc, "— End of PCMS Complete Project Run Guide —")

    doc.core_properties.title = "PCMS Complete Project Run Guide (Bangla)"
    doc.core_properties.subject = "Step-by-step Windows, PHP, OCI8, Oracle and PCMS run instructions"
    doc.core_properties.author = "PCMS Project"
    doc.core_properties.keywords = "PCMS, PHP, Oracle, OCI8, Windows, run guide, Bangla"
    doc.core_properties.comments = "Commands verified against the local PCMS workspace on 18 September 2026."
    doc.save(OUT)
    print(OUT)


if __name__ == "__main__":
    build()
