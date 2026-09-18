from __future__ import annotations

from pathlib import Path

from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.style import WD_STYLE_TYPE
from docx.enum.table import WD_CELL_VERTICAL_ALIGNMENT, WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK, WD_LINE_SPACING
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Inches, Pt, RGBColor


ROOT = Path(r"E:\advance_database")
OUT = ROOT / "PCMS_PHP_Oracle_Connection_and_Video_Guide.docx"
IMG = Path(r"C:\Users\HP\AppData\Local\Temp\pcms_doc_images_20260918")

PURPLE = "5324D6"
PURPLE_DARK = "32158A"
LAVENDER = "F2EEFF"
PALE = "F7F7FA"
GRAY = "5C6470"
LIGHT_GRAY = "D9DCE3"
GREEN = "19764B"
RED = "A61B1B"
WHITE = "FFFFFF"
BLACK = "111111"


def set_cell_shading(cell, fill: str) -> None:
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = tc_pr.find(qn("w:shd"))
    if shd is None:
        shd = OxmlElement("w:shd")
        tc_pr.append(shd)
    shd.set(qn("w:fill"), fill)


def set_cell_border(cell, color: str = LIGHT_GRAY, size: int = 6) -> None:
    tc_pr = cell._tc.get_or_add_tcPr()
    borders = tc_pr.first_child_found_in("w:tcBorders")
    if borders is None:
        borders = OxmlElement("w:tcBorders")
        tc_pr.append(borders)
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        tag = qn(f"w:{edge}")
        el = borders.find(tag)
        if el is None:
            el = OxmlElement(f"w:{edge}")
            borders.append(el)
        el.set(qn("w:val"), "single")
        el.set(qn("w:sz"), str(size))
        el.set(qn("w:color"), color)


def set_cell_margins(cell, top=90, start=110, bottom=90, end=110) -> None:
    tc = cell._tc
    tc_pr = tc.get_or_add_tcPr()
    tc_mar = tc_pr.first_child_found_in("w:tcMar")
    if tc_mar is None:
        tc_mar = OxmlElement("w:tcMar")
        tc_pr.append(tc_mar)
    for m, val in (("top", top), ("start", start), ("bottom", bottom), ("end", end)):
        node = tc_mar.find(qn(f"w:{m}"))
        if node is None:
            node = OxmlElement(f"w:{m}")
            tc_mar.append(node)
        node.set(qn("w:w"), str(val))
        node.set(qn("w:type"), "dxa")


def set_repeat_table_header(row) -> None:
    tr_pr = row._tr.get_or_add_trPr()
    tbl_header = OxmlElement("w:tblHeader")
    tbl_header.set(qn("w:val"), "true")
    tr_pr.append(tbl_header)


def keep_with_next(paragraph) -> None:
    paragraph.paragraph_format.keep_with_next = True


def set_run_font(run, name="Nirmala UI", size=None, bold=None, color=None) -> None:
    run.font.name = name
    run._element.rPr.rFonts.set(qn("w:eastAsia"), name)
    run._element.rPr.rFonts.set(qn("w:cs"), name)
    if size:
        run.font.size = Pt(size)
    if bold is not None:
        run.bold = bold
    if color:
        run.font.color.rgb = RGBColor.from_string(color)


def add_page_number(paragraph) -> None:
    paragraph.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    run = paragraph.add_run("PCMS PHP–Oracle Connection Guide   •   ")
    set_run_font(run, size=8, color=GRAY)
    begin = OxmlElement("w:fldChar")
    begin.set(qn("w:fldCharType"), "begin")
    instr = OxmlElement("w:instrText")
    instr.set(qn("xml:space"), "preserve")
    instr.text = "PAGE"
    end = OxmlElement("w:fldChar")
    end.set(qn("w:fldCharType"), "end")
    run._r.append(begin)
    run._r.append(instr)
    run._r.append(end)


def add_heading(doc, text: str, level: int = 1):
    p = doc.add_paragraph(style=f"Heading {level}")
    p.add_run(text)
    keep_with_next(p)
    return p


def add_body(doc, text: str, bold_prefix: str | None = None):
    p = doc.add_paragraph(style="Body Text")
    if bold_prefix and text.startswith(bold_prefix):
        r = p.add_run(bold_prefix)
        r.bold = True
        p.add_run(text[len(bold_prefix):])
    else:
        p.add_run(text)
    return p


def add_bullets(doc, items, level=0):
    for item in items:
        p = doc.add_paragraph(style="List Bullet" if level == 0 else "List Bullet 2")
        p.add_run(item)


def add_numbered(doc, items):
    for number, item in enumerate(items, start=1):
        p = doc.add_paragraph(style="Body Text")
        p.paragraph_format.left_indent = Cm(0.62)
        p.paragraph_format.first_line_indent = Cm(-0.62)
        p.paragraph_format.keep_together = True
        r = p.add_run(f"{number}.  ")
        r.bold = True
        p.add_run(item)


def add_code(doc, code: str):
    for line in code.rstrip().splitlines():
        p = doc.add_paragraph()
        p.paragraph_format.left_indent = Cm(0.35)
        p.paragraph_format.right_indent = Cm(0.2)
        p.paragraph_format.space_after = Pt(0)
        p.paragraph_format.line_spacing = 1.0
        p.paragraph_format.keep_together = True
        p_pr = p._p.get_or_add_pPr()
        shd = OxmlElement("w:shd")
        shd.set(qn("w:fill"), "F3F4F6")
        p_pr.append(shd)
        r = p.add_run(line if line else " ")
        set_run_font(r, name="Consolas", size=8.5, color="1E2430")


def add_note(doc, title: str, text: str, color=PURPLE):
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = True
    cell = table.cell(0, 0)
    set_cell_shading(cell, "F7F5FF")
    set_cell_border(cell, color, 8)
    set_cell_margins(cell, 130, 150, 130, 150)
    p = cell.paragraphs[0]
    r = p.add_run(title + "  ")
    set_run_font(r, size=10, bold=True, color=color)
    r = p.add_run(text)
    set_run_font(r, size=10, color=BLACK)
    doc.add_paragraph().paragraph_format.space_after = Pt(0)


def add_table(doc, headers, rows, widths=None, header_color=PURPLE):
    table = doc.add_table(rows=1, cols=len(headers))
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.autofit = False
    hdr = table.rows[0]
    set_repeat_table_header(hdr)
    for i, h in enumerate(headers):
        c = hdr.cells[i]
        set_cell_shading(c, header_color)
        set_cell_border(c)
        set_cell_margins(c)
        c.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
        p = c.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.LEFT
        r = p.add_run(str(h))
        set_run_font(r, size=9, bold=True, color=WHITE)
        if widths:
            c.width = Inches(widths[i])
    for ri, row in enumerate(rows):
        cells = table.add_row().cells
        for i, value in enumerate(row):
            c = cells[i]
            set_cell_shading(c, LAVENDER if ri % 2 == 0 else WHITE)
            set_cell_border(c)
            set_cell_margins(c)
            c.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.TOP
            p = c.paragraphs[0]
            r = p.add_run(str(value))
            set_run_font(r, size=8.6, color=BLACK)
            if widths:
                c.width = Inches(widths[i])
    doc.add_paragraph().paragraph_format.space_after = Pt(0)
    return table


def add_screenshot(doc, filename: str, caption: str):
    path = IMG / filename
    if path.exists():
        p = doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p.paragraph_format.keep_together = True
        p.add_run().add_picture(str(path), width=Inches(6.2))
        c = doc.add_paragraph()
        c.alignment = WD_ALIGN_PARAGRAPH.CENTER
        c.paragraph_format.keep_with_next = False
        r = c.add_run(caption)
        set_run_font(r, size=8.5, color=GRAY)


def add_page_break(doc):
    doc.add_page_break()


def configure_document(doc: Document) -> None:
    sec = doc.sections[0]
    sec.page_width = Inches(8.5)
    sec.page_height = Inches(11)
    sec.top_margin = Inches(0.7)
    sec.bottom_margin = Inches(0.65)
    sec.left_margin = Inches(0.72)
    sec.right_margin = Inches(0.72)
    sec.header_distance = Inches(0.3)
    sec.footer_distance = Inches(0.3)

    styles = doc.styles
    normal = styles["Normal"]
    normal.font.name = "Nirmala UI"
    normal._element.rPr.rFonts.set(qn("w:eastAsia"), "Nirmala UI")
    normal._element.rPr.rFonts.set(qn("w:cs"), "Nirmala UI")
    normal.font.size = Pt(10.5)
    normal.font.color.rgb = RGBColor.from_string(BLACK)
    normal.paragraph_format.space_after = Pt(6)
    normal.paragraph_format.line_spacing = 1.12

    for style_name in ("Body Text", "List Bullet", "List Bullet 2", "List Number"):
        st = styles[style_name]
        st.font.name = "Nirmala UI"
        st._element.rPr.rFonts.set(qn("w:eastAsia"), "Nirmala UI")
        st._element.rPr.rFonts.set(qn("w:cs"), "Nirmala UI")
        st.font.size = Pt(10.5)
        st.font.color.rgb = RGBColor.from_string(BLACK)
        st.paragraph_format.space_after = Pt(4)
        st.paragraph_format.line_spacing = 1.12

    heading_specs = {
        "Title": (24, BLACK, 0, 10),
        "Subtitle": (12, GRAY, 0, 6),
        "Heading 1": (17, BLACK, 14, 7),
        "Heading 2": (13, BLACK, 10, 5),
        "Heading 3": (11, PURPLE_DARK, 8, 3),
    }
    for name, (size, color, before, after) in heading_specs.items():
        st = styles[name]
        st.font.name = "Nirmala UI"
        st._element.rPr.rFonts.set(qn("w:eastAsia"), "Nirmala UI")
        st._element.rPr.rFonts.set(qn("w:cs"), "Nirmala UI")
        st.font.size = Pt(size)
        st.font.bold = True
        st.font.color.rgb = RGBColor.from_string(color)
        st.paragraph_format.space_before = Pt(before)
        st.paragraph_format.space_after = Pt(after)
        st.paragraph_format.keep_with_next = True

    add_page_number(sec.footer.paragraphs[0])


def build() -> None:
    doc = Document()
    configure_document(doc)

    # Cover
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(65)
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("PCMS")
    set_run_font(r, size=15, bold=True, color=PURPLE)
    p = doc.add_paragraph(style="Title")
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.add_run("PHP থেকে Oracle Database Connection")
    p = doc.add_paragraph(style="Title")
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.add_run("ও Video Recording Guide")
    p = doc.add_paragraph(style="Subtitle")
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.add_run("DatabaseConnectionTutorial.pptx-এর workflow অনুসারে PCMS-এর জন্য সম্পূর্ণ Bangla নির্দেশিকা")
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(30)
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("Programming Contest Management System (PCMS)")
    set_run_font(r, size=12, bold=True, color=BLACK)
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("প্রস্তুতকরণ তারিখ: ১৮ সেপ্টেম্বর ২০২৬")
    set_run_font(r, size=10, color=GRAY)
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(65)
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run("এই guide অনুসরণ করে setup, verification এবং tutorial video—সবই পুনরায় করা যাবে।")
    set_run_font(r, size=10, color=PURPLE_DARK)

    add_page_break(doc)
    add_heading(doc, "কীভাবে এই document ব্যবহার করবেন", 1)
    add_body(doc, "এই document তিনটি কাজ একসঙ্গে করে: প্রথমত, PPTX-এর embedded video-তে দেখানো Oracle–PHP connection workflow ব্যাখ্যা করে; দ্বিতীয়ত, সেই workflow-কে আপনার PCMS project-এর architecture ও security rules অনুযায়ী প্রয়োগ করে; তৃতীয়ত, একই ধরনের tutorial video বানানোর জন্য scene-by-scene screen action এবং Bangla narration script দেয়।")
    add_note(doc, "গুরুত্বপূর্ণ", "ভিডিওর sample code-এ username/password source file-এ লেখা হয়েছিল। PCMS-এ এটি নিরাপদ নয়। এখানে password .env file-এ থাকবে এবং video-তে কখনো দৃশ্যমান হবে না।")
    add_heading(doc, "এই guide শেষ করলে যা পারবেন", 2)
    add_bullets(doc, [
        "Windows-এ PHP build-এর সঙ্গে matching OCI8 ও Oracle Instant Client install করতে পারবেন।",
        "CLI অথবা XAMPP/Apache—দুই পরিবেশেই OCI8 enable ও verify করতে পারবেন।",
        "PCMS-এর .env এবং app/Support/Database.php ব্যবহার করে Oracle XE-তে নিরাপদ connection করতে পারবেন।",
        "Connection test, live HTTP test এবং role-based dashboard demonstration record করতে পারবেন।",
        "সাধারণ OCI8/Oracle error শনাক্ত করে দ্রুত সমাধান করতে পারবেন।",
        "প্রায় ১৮–২০ মিনিটের একটি পরিষ্কার, credential-safe tutorial video বানাতে পারবেন।",
    ])
    add_heading(doc, "সূচিপত্র", 2)
    toc_rows = [
        ("১", "PPTX/video analysis ও মূল workflow"),
        ("২", "PCMS-এর verified configuration"),
        ("৩", "Connection setup: বর্তমান PHP/CLI পদ্ধতি"),
        ("৪", "XAMPP/Apache পদ্ধতি—video-র মতো"),
        ("৫", "PCMS code architecture ও database flow"),
        ("৬", "Verification এবং expected result"),
        ("৭", "Troubleshooting matrix"),
        ("৮", "Video preparation ও scene-by-scene script"),
        ("৯", "Recording, editing ও final QA checklist"),
        ("Appendix", "Commands, safe sample files ও quick reference"),
    ]
    add_table(doc, ["অংশ", "বিষয়"], toc_rows, widths=[1.05, 5.55])

    add_page_break(doc)
    add_heading(doc, "১. PPTX এবং embedded video-এর গভীর analysis", 1)
    add_body(doc, "DatabaseConnectionTutorial.pptx-এর ৫টি slide এবং প্রায় ২৩ মিনিট ৩৩ সেকেন্ডের embedded tutorial video পর্যবেক্ষণ করে নিচের end-to-end workflow পাওয়া গেছে। Video-টির লক্ষ্য হলো PHP application থেকে Oracle database-এ OCI8 ব্যবহার করে connection স্থাপন, query চালানো এবং browser-এ data দেখানো।")
    add_heading(doc, "১.১ Video-র original workflow", 2)
    add_numbered(doc, [
        "প্রথমে working application এবং database data browser-এ দেখানো।",
        "XAMPP Control Panel থেকে Apache environment প্রস্তুত করা।",
        "Active php.ini file খুলে Oracle OCI8 extension enable করা।",
        "Oracle Instant Client download ও extract করা।",
        "Instant Client folder Windows user Path-এ যোগ করা।",
        "oci.dll, oraociei19.dll এবং oraons.dll Apache-এর bin folder-এ copy করা।",
        "Apache restart করে phpinfo() থেকে OCI8 section দেখা।",
        "MVC-style project-এ connection file, model query, controller এবং view তৈরি করা।",
        "Browser route open করে Oracle table-এর fetched data প্রদর্শন করা।",
    ])
    add_screenshot(doc, "tutorial_0240.png", "Original tutorial reference — php.ini-তে OCI8 extension খোঁজা ও enable করা")
    add_screenshot(doc, "tutorial_0330.png", "Original tutorial reference — Instant Client directory Windows Path-এ যোগ করা")
    add_heading(doc, "১.২ Video-র শক্তিশালী দিক", 2)
    add_bullets(doc, [
        "Installation থেকে final browser output পর্যন্ত পূর্ণ sequence দেখায়।",
        "php.ini, PATH এবং runtime DLL—তিনটি আলাদা dependency-র ভূমিকা বোঝায়।",
        "Query execution-এর জন্য oci_parse(), oci_execute() এবং oci_fetch_assoc() ব্যবহার দেখায়।",
        "Connection হওয়ার প্রমাণ হিসেবে phpinfo() এবং real database result—দুটিই দেখায়।",
    ])
    add_heading(doc, "১.৩ PCMS-এর জন্য যেসব correction জরুরি", 2)
    add_table(doc, ["Tutorial pattern", "সমস্যা", "PCMS-এ সঠিক প্রয়োগ"], [
        ("Source code-এ username/password", "Credential leak এবং version-control risk", ".env file; repository-তে commit নয়; video-তে mask/blur"),
        ("প্রতিটি model থেকে connection helper", "Duplication এবং inconsistent error handling", "একটি shared app/Support/Database.php adapter"),
        ("Direct output/echo", "Production error details প্রকাশ হতে পারে", "Exception handling, APP_DEBUG এবং log-based diagnosis"),
        ("String-built SQL", "SQL injection ও quoting error", "OCI named binds—যেমন :user_id, :contest_id"),
        ("শুধু browser result", "Regression বা authorization যাচাই হয় না", "CLI connection test + smoke test + live HTTP role checks"),
    ], widths=[1.45, 2.25, 2.9])
    add_page_break(doc)
    add_heading(doc, "২. আপনার PCMS-এর verified configuration", 1)
    add_body(doc, "এই PCMS workspace-এ Oracle connection ইতোমধ্যে বাস্তবে configure ও verify করা হয়েছে। নিচের মানগুলো এই computer-এর confirmed state; অন্য computer-এ version অনুযায়ী matching build বেছে নিতে হবে।")
    add_table(doc, ["Component", "Verified value"], [
        ("PHP", "8.5.5, x64, Non-Thread-Safe, Visual C++ 2022"),
        ("Active php.ini", r"C:\php\php.ini"),
        ("OCI8 extension", "OCI8 3.4.1 — php_oci8_19.dll"),
        ("PHP extension folder", r"C:\php\ext"),
        ("Oracle Instant Client", r"19.26 — C:\Oracle\instantclient_19_26"),
        ("Oracle service", "Local Oracle XE; service name XE; port 1521"),
        ("Application schema", "PCMS_APP"),
        ("Database charset", "AL32UTF8"),
        ("Current web run mode", "PHP built-in server; document root public/; port 8765"),
        ("Connection class", r"app\Support\Database.php"),
    ], widths=[2.1, 4.5])
    add_note(doc, "দুটি setup mode", "Mode A হলো আপনার বর্তমান verified PHP/CLI setup—এখনই ব্যবহারযোগ্য। Mode B হলো XAMPP/Apache setup—PPTX video-র visual process-এর সবচেয়ে কাছাকাছি।")
    add_heading(doc, "২.১ Version matching rule", 2)
    add_body(doc, "OCI8 DLL বাছাইয়ের সময় PHP-এর চারটি বৈশিষ্ট্য অবশ্যই মিলতে হবে: PHP major/minor version, x64/x86 architecture, Thread Safe (TS) অথবা Non-Thread-Safe (NTS), এবং compiler/runtime family। এগুলোর একটি না মিললেও PHP extension load হবে না।")
    add_code(doc, "php -v\nphp -i | findstr /I \"Architecture Thread Safety Compiler extension_dir\"\nphp --ini")
    add_body(doc, "Expected: Architecture x64; Thread Safety disabled হলে NTS OCI8 build; enabled হলে TS build; Loaded Configuration File সঠিক php.ini দেখাবে।")

    add_page_break(doc)
    add_heading(doc, "৩. Mode A — PCMS-এর বর্তমান PHP/CLI connection setup", 1)
    add_heading(doc, "৩.১ Prerequisites", 2)
    add_bullets(doc, [
        "Oracle Database XE service চলমান এবং listener port 1521 accessible।",
        "PCMS_APP schema তৈরি এবং database scripts প্রয়োগ করা হয়েছে।",
        "PHP executable install করা আছে এবং terminal থেকে php command চলে।",
        "Matching OCI8 package এবং Oracle Instant Client 19.x পাওয়া আছে।",
        "Project root E:\\advance_database; public directory web document root।",
    ])
    add_heading(doc, "৩.২ Oracle Instant Client install", 2)
    add_numbered(doc, [
        "Oracle Instant Client Basic package-এর Windows x64 build download করুন।",
        r"ZIP extract করে C:\Oracle\instantclient_19_26-এর মতো versioned folder রাখুন।",
        "Folder-এর ভিতরে oci.dll এবং oraociei19.dll আছে কি না যাচাই করুন।",
        "Windows Environment Variables → User variables → Path → New থেকে Instant Client folder যোগ করুন।",
        "পুরোনো terminal বন্ধ করে নতুন PowerShell খুলুন, যাতে নতুন Path load হয়।",
    ])
    add_code(doc, 'where.exe oci.dll\nGet-ChildItem "C:\\Oracle\\instantclient_19_26" -Filter "*.dll" | Select-Object Name')
    add_heading(doc, "৩.৩ OCI8 extension install ও php.ini configure", 2)
    add_numbered(doc, [
        r"Matching php_oci8_19.dll file C:\php\ext folder-এ রাখুন।",
        "php --ini দিয়ে কোন php.ini active তা নিশ্চিত করুন।",
        "Active php.ini-তে extension_dir এবং extension line যোগ/enable করুন।",
        "CLI process DLL resolve না করলে tutorial-এর তিনটি runtime DLL php.exe-এর পাশে রাখুন অথবা Instant Client Path-এর প্রথম দিকে রাখুন।",
    ])
    add_code(doc, '[PHP]\nextension_dir = "C:\\php\\ext"\nextension = php_oci8_19.dll\ndate.timezone = Asia/Dhaka\ndefault_charset = "UTF-8"')
    add_note(doc, "Semicolon rule", "php.ini line-এর শুরুতে ; থাকলে সেটি comment। OCI8 enable করতে extension line-এর শুরু থেকে ; সরাতে হবে।")
    add_heading(doc, "৩.৪ OCI8 load verify", 2)
    add_code(doc, "php --ini\nphp -m | findstr /I oci8\nphp --ri oci8")
    add_body(doc, "Expected key output: OCI8 Support => enabled, OCI8 Version => 3.4.1, এবং Oracle Run-time Client Library Version => 19.26.0.0.0।")

    add_page_break(doc)
    add_heading(doc, "৩.৫ PCMS .env configure", 2)
    add_body(doc, ".env.example copy করে .env বানান। আসল password কেবল local .env-তে লিখবেন। .env Git-ignored থাকতে হবে। Video recording-এর সময় এই file খুলবেন না; প্রয়োজন হলে sample .env.example দেখাবেন।")
    add_code(doc, "APP_ENV=local\nAPP_DEBUG=true\nAPP_URL=http://127.0.0.1:8765\nAPP_TIMEZONE=Asia/Dhaka\nAPP_KEY=<LONG_RANDOM_SECRET>\n\nDB_CONNECTION=oci8\nDB_HOST=127.0.0.1\nDB_PORT=1521\nDB_SERVICE=XE\nDB_USERNAME=PCMS_APP\nDB_PASSWORD=<DB_PASSWORD>\nDB_CHARSET=AL32UTF8")
    add_note(doc, "Recording-safe practice", "Screen-এ .env নয়, .env.example দেখান। DB_PASSWORD-এর value সর্বদা <DB_PASSWORD> placeholder রাখুন।")
    add_heading(doc, "৩.৬ Oracle service check", 2)
    add_code(doc, "Get-Service | Where-Object { $_.Name -match 'Oracle|TNS' }\ntnsping XE")
    add_body(doc, "Service stopped থাকলে Windows Services থেকে OracleServiceXE এবং listener-related service start করুন। tnsping না থাকলে PHP connection test-ই practical verification হিসেবে ব্যবহার করা যায়।")
    add_heading(doc, "৩.৭ Project connection test", 2)
    add_code(doc, "cd E:\\advance_database\nphp tests/oracle_connection.php")
    add_body(doc, "Expected result: “OCI8 connection passed.”; database banner; users, contests ইত্যাদির canonical seed count। Error হলে browser চালানোর আগে Troubleshooting section অনুসরণ করুন।")
    add_heading(doc, "৩.৮ Application run", 2)
    add_code(doc, "php -S 127.0.0.1:8765 -t public public/router.php")
    add_body(doc, "Browser-এ http://127.0.0.1:8765 খুলুন। Server চলমান terminal বন্ধ করবেন না। Apache deployment-এ URL ও document root আলাদা হবে।")

    add_page_break(doc)
    add_heading(doc, "৪. Mode B — XAMPP/Apache setup, tutorial video-র মতো", 1)
    add_body(doc, "PPTX-এর video XAMPP ব্যবহার করে। PCMS-কে একইভাবে Apache-তে চালাতে চাইলে নিচের sequence অনুসরণ করুন। তবে XAMPP-এর bundled PHP সাধারণত Thread Safe; তাই বর্তমান NTS DLL সরাসরি copy করা যাবে না—matching TS OCI8 build ব্যবহার করতে হবে।")
    add_heading(doc, "৪.১ XAMPP-এর PHP build যাচাই", 2)
    add_code(doc, 'C:\\xampp\\php\\php.exe -v\nC:\\xampp\\php\\php.exe -i | findstr /I "Architecture Thread Safety Compiler"')
    add_body(doc, "Output অনুযায়ী exact PHP version, x64/x86, TS/NTS এবং compiler matching OCI8 package নির্বাচন করুন। XAMPP PHP এবং standalone C:\\php PHP-কে মিশিয়ে ফেলবেন না।")
    add_heading(doc, "৪.২ XAMPP php.ini edit", 2)
    add_numbered(doc, [
        "XAMPP Control Panel → Apache → Config → PHP (php.ini) খুলুন।",
        "Ctrl+F দিয়ে oci8 খুঁজুন।",
        "PHP/Instant Client generation অনুযায়ী extension=php_oci8_19.dll line enable করুন। পুরোনো package হলে নাম ভিন্ন হতে পারে।",
        "extension_dir XAMPP-এর ext folder নির্দেশ করছে কি না নিশ্চিত করুন।",
        "File save করুন।",
    ])
    add_code(doc, 'extension_dir="C:\\xampp\\php\\ext"\nextension=php_oci8_19.dll')
    add_screenshot(doc, "tutorial_0240.png", "Video-র exact visual step — php.ini-তে OCI8 extension enable")
    add_heading(doc, "৪.৩ Instant Client এবং DLL visibility", 2)
    add_numbered(doc, [
        r"Instant Client C:\Oracle\instantclient_19_26-এ extract করুন।",
        "Folder-টি Windows user/system Path-এ যোগ করুন।",
        r"Tutorial-এর মতো oci.dll, oraociei19.dll এবং oraons.dll C:\xampp\apache\bin-এ copy করতে পারেন।",
        "File version mismatch এড়াতে তিনটি DLL একই Instant Client folder থেকে নিন।",
        "XAMPP Control Panel থেকে Apache Stop, তারপর Start করুন।",
    ])
    add_screenshot(doc, "tutorial_0390.png", "Video-র exact visual step — তিনটি Oracle DLL Apache bin folder-এ নেওয়া")
    add_heading(doc, "৪.৪ phpinfo() দিয়ে verification", 2)
    add_body(doc, "শুধু local development-এর জন্য public folder-এ temporary phpinfo file বানিয়ে browser-এ খুলুন। Search করুন “OCI8 Support”, “OCI8 Version” এবং “Oracle Run-time Client Library Version”। Verification শেষ হলে file delete করুন—production বা submission package-এ phpinfo file রাখবেন না।")
    add_code(doc, "<?php\nphpinfo();")
    add_note(doc, "Security", "phpinfo() server path, modules এবং environment details প্রকাশ করে। Video capture শেষে temporary file অবশ্যই মুছুন।", RED)
    add_heading(doc, "৪.৫ Apache document root", 2)
    add_body(doc, "PCMS-এর web root project root নয়; public/ directory। Apache VirtualHost অথবা Alias-এর DocumentRoot E:/advance_database/public দিন এবং AllowOverride All enable করুন, যাতে .htaccess/front-controller routing কাজ করে।")
    add_code(doc, '<VirtualHost *:80>\n    ServerName pcms.local\n    DocumentRoot "E:/advance_database/public"\n    <Directory "E:/advance_database/public">\n        AllowOverride All\n        Require all granted\n    </Directory>\n</VirtualHost>')

    add_page_break(doc)
    add_heading(doc, "৫. PCMS code architecture এবং database flow", 1)
    add_body(doc, "Original tutorial একটি dbconn.php file থেকে model function-এ connection নেয়। PCMS একই ধারণা আরও নিরাপদ ও maintainableভাবে একটি shared Database adapter-এ centralize করেছে।")
    add_screenshot(doc, "tutorial_0570.png", "Original tutorial reference — connection helper শুরু করা (credential value ছাড়াই)")
    add_heading(doc, "৫.১ Request-to-database flow", 2)
    add_table(doc, ["ধাপ", "PCMS component", "দায়িত্ব"], [
        ("1", "public/index.php + router", "HTTP request গ্রহণ ও route resolve"),
        ("2", "Middleware", "Authentication এবং role authorization"),
        ("3", "Controller", "Input গ্রহণ, validation/service call, response নির্বাচন"),
        ("4", "Service / Validation", "Business rule এবং reusable validation"),
        ("5", "Repository", "SQL এবং named parameter values প্রস্তুত"),
        ("6", "app/Support/Database.php", "OCI8 connection, parse, bind, execute, fetch, transaction"),
        ("7", "Oracle XE", "Persistent PCMS data ও PL/SQL program units"),
        ("8", "View", "Escaped HTML output; SQL নয়"),
    ], widths=[0.45, 2.05, 4.1])
    add_heading(doc, "৫.২ Connection descriptor", 2)
    add_body(doc, "Database::connection() .env থেকে host, port, service, username, password এবং charset নেয়। PCMS descriptor তৈরি করে:")
    add_code(doc, "$descriptor = sprintf('//%s:%s/%s',\n    Env::get('DB_HOST', '127.0.0.1'),\n    Env::get('DB_PORT', '1521'),\n    Env::get('DB_SERVICE', 'XE')\n);\n\n$connection = oci_connect(\n    Env::get('DB_USERNAME', ''),\n    Env::get('DB_PASSWORD', ''),\n    $descriptor,\n    Env::get('DB_CHARSET', 'AL32UTF8')\n);")
    add_heading(doc, "৫.৩ Named binds কেন জরুরি", 2)
    add_body(doc, "Repository SQL-এ user input কখনো string concatenate করবেন না। Placeholder ব্যবহার করে oci_bind_by_name() call করুন। এতে SQL injection risk কমে এবং Oracle type/quoting handling নির্ভরযোগ্য হয়।")
    add_code(doc, "$sql = 'SELECT * FROM contests WHERE status = :status';\n$rows = Database::all($sql, ['status' => 'PUBLISHED']);")
    add_body(doc, "PCMS Database adapter parameter key-এর সামনে colon যোগ করে, bind value জীবিত রাখে, তারপর statement execute করে। SELECT result lowercase associative keys-এ normalize হয়।")
    add_screenshot(doc, "tutorial_0810.png", "Original tutorial reference — model layer-এ oci_parse/execute/fetch sequence")
    add_heading(doc, "৫.৪ Transaction pattern", 2)
    add_body(doc, "একাধিক write operation একসঙ্গে সফল হওয়া দরকার হলে Database::transaction() ব্যবহার করুন। Success হলে commit; exception হলে rollback। Contest creation, registration approval বা multi-table submission workflow-এ এটি গুরুত্বপূর্ণ।")
    add_page_break(doc)
    add_heading(doc, "৬. Verification — connection থেকে full workflow", 1)
    add_heading(doc, "৬.১ CLI verification sequence", 2)
    add_code(doc, "php --ini\nphp --ri oci8\nphp tests/oracle_connection.php\nphp tests/smoke.php")
    add_table(doc, ["Check", "Pass evidence"], [
        ("PHP config", r"Loaded Configuration File = C:\php\php.ini"),
        ("OCI8", "OCI8 Support enabled; version 3.4.1; runtime client 19.26"),
        ("Oracle login", "OCI8 connection passed; Oracle XE banner returned"),
        ("Seed data", "Expected PCMS users/contests counts returned"),
        ("Application logic", "68 smoke assertions passed"),
        ("Oracle objects", "Invalid objects = 0; USER_ERRORS = 0"),
    ], widths=[2.0, 4.6])
    add_heading(doc, "৬.২ HTTP verification", 2)
    add_code(doc, "# Terminal 1\nphp -S 127.0.0.1:8765 -t public public/router.php\n\n# Terminal 2\npowershell -ExecutionPolicy Bypass -File tests/live_http.ps1")
    add_body(doc, "Expected: 13 live HTTP checks pass—public database pages, participant/organizer/admin dashboards, role-specific pages এবং representative 403 authorization boundaries।")
    add_heading(doc, "৬.৩ Manual browser demo", 2)
    add_numbered(doc, [
        "Home page load করে header, navigation এবং contest cards দেখান।",
        "Login page-এ synthetic demo account ব্যবহার করুন; password field masked রাখুন।",
        "Participant dashboard: contest browsing, registration/submission/history flow দেখান।",
        "Organizer dashboard: contest/problem/submission management flow দেখান।",
        "Admin dashboard: users, roles, status/audit controls দেখান।",
        "Unauthorized route open করলে 403 boundary দেখান—role security বাস্তবে কাজ করছে।",
        "Logout করে session শেষ করুন।",
    ])
    add_screenshot(doc, "tutorial_1380.png", "Original tutorial reference — Oracle থেকে fetched data browser table-এ প্রদর্শন")
    add_note(doc, "Verified outcome", "এই workspace-এ OCI8 connection, Oracle objects, smoke assertions এবং live HTTP role checks সফলভাবে চালানো হয়েছে। Video-তে একই evidence পুনরায় দেখান।", GREEN)

    add_page_break(doc)
    add_heading(doc, "৭. Troubleshooting matrix", 1)
    add_table(doc, ["লক্ষণ / error", "সম্ভাব্য কারণ", "সমাধান"], [
        ("PHP Startup: Unable to load dynamic library", "Wrong DLL path/version/architecture/TS mode", "php -v ও php -i মিলিয়ে matching OCI8 build; extension_dir ও filename ঠিক করুন"),
        ("The specified module could not be found", "OCI8 DLL আছে, dependency নেই", "Instant Client Path-এ দিন; একই package-এর runtime DLL visible করুন; নতুন terminal/Apache restart"),
        ("php -m-এ oci8 নেই", "Wrong php.ini edit", "php --ini দেখে active file edit করুন; semicolon সরান"),
        ("ORA-12541: no listener", "Oracle listener/service বন্ধ বা port ভুল", "Oracle services start; DB_HOST/DB_PORT যাচাই; listener status check"),
        ("ORA-12514", "Service name listener জানে না", "DB_SERVICE=XE অথবা actual service; listener/service registration verify"),
        ("ORA-12154", "Connect identifier resolve হয়নি", "EZ Connect descriptor //host:port/service ব্যবহার; spelling check"),
        ("ORA-01017", "Username/password ভুল", ".env credential যাচাই; case/lock status পরীক্ষা; source code-এ password লিখবেন না"),
        ("ORA-28040", "Client/server authentication compatibility", "Supported Instant Client 19.x ব্যবহার; server policy DBA-এর সঙ্গে যাচাই"),
        ("ORA-01745 invalid host/bind variable name", "Reserved/problematic bind যেমন :start/:end", "নিরাপদ bind নাম যেমন :row_start এবং :row_end"),
        ("CLI works, Apache fails", "Apache ভিন্ন PHP/php.ini/Path ব্যবহার করছে", "phpinfo থেকে Apache config path দেখুন; Apache-specific extension/DLL এবং restart"),
        ("Login-এর পরে HTTP 500", "View variable name application data shadow করছে", "Renderer payload variable আলাদা নাম দিন; logs দেখুন; display_errors production-এ off"),
        ("Address already in use", "Port 8765 অন্য process ব্যবহার করছে", "Running server reuse/stop করুন অথবা অন্য port নিয়ে APP_URL update করুন"),
    ], widths=[1.55, 2.15, 2.9])
    add_heading(doc, "৭.১ Safe diagnostic commands", 2)
    add_code(doc, "php --ini\nphp -v\nphp -m | findstr /I oci8\nphp --ri oci8\nwhere.exe php\nwhere.exe oci.dll\nnetstat -ano | findstr :1521\nnetstat -ano | findstr :8765")
    add_heading(doc, "৭.২ Error দেখালে video-তে কী বলবেন", 2)
    add_body(doc, "“Error-টি connection code-এর logic না-ও হতে পারে। প্রথমে active PHP binary, active php.ini, OCI8 build matching, Instant Client visibility এবং Oracle listener—এই পাঁচটি layer আলাদা করে পরীক্ষা করব।” এই ভাষা ব্যবহার করলে viewer একটি repeatable diagnostic method পাবে।")

    add_heading(doc, "৮. Video বানানোর প্রস্তুতি", 1)
    add_heading(doc, "৮.১ Recording-এর আগে checklist", 2)
    add_bullets(doc, [
        "Screen resolution 1920×1080; Windows scaling 100% বা 125%; editor zoom অন্তত 125%।",
        "Browser bookmarks bar, personal email, notifications এবং unrelated tabs hide করুন।",
        ".env বন্ধ রাখুন; .env.example খুলুন; terminal history পরিষ্কার বা নতুন terminal নিন।",
        "Editor Explorer-এ শুধু relevant PCMS folders দেখান।",
        "Oracle service, OCI8 এবং application আগে একবার test করুন—recording-এর মধ্যে surprise এড়াতে।",
        "Microphone test করুন; fan/noise কমান; 48 kHz audio হলে ভালো।",
        "Mouse pointer visible; slow deliberate movement; click highlight optional।",
        "প্রতি scene আলাদা clip হিসেবে record করলে ভুল কাটা সহজ হয়।",
        "একটি backup copy রাখুন; database reset বা destructive command video-তে চালাবেন না।",
    ])
    add_heading(doc, "৮.২ Suggested tools/settings", 2)
    add_table(doc, ["Item", "Recommended setting"], [
        ("Recorder", "OBS Studio অথবা Windows Snipping Tool screen recording"),
        ("Canvas / Output", "1920×1080, 30 fps"),
        ("Encoder", "Hardware H.264 available হলে ব্যবহার"),
        ("Audio", "Mono voice acceptable; peak প্রায় -12 dB থেকে -6 dB"),
        ("Cursor", "Visible; unnecessary fast movement নয়"),
        ("Code font", "Consolas/Cascadia Mono; 18–22 px equivalent"),
        ("Export", "MP4, H.264, 1080p, 8–12 Mbps"),
    ], widths=[2.0, 4.6])
    add_heading(doc, "৮.৩ Video structure", 2)
    add_body(doc, "Target duration ১৮–২০ মিনিট। প্রথমে final result দেখান, তারপর setup, code, verification এবং role demo। এটি original tutorial-এর outcome-first style বজায় রাখে কিন্তু PCMS-এর security ও testing আরও পরিষ্কারভাবে দেখায়।")

    add_page_break(doc)
    add_heading(doc, "৯. Scene-by-scene video script", 1)
    add_body(doc, "নিচের সময়গুলো guideline। নিজের speaking speed অনুযায়ী কয়েক সেকেন্ড কমবেশি করতে পারেন। প্রতিটি scene-এ Screen action অনুসরণ করুন এবং Narration অংশটি স্বাভাবিকভাবে বলুন।")

    scenes = [
        ("Scene 1 — Title ও final preview", "00:00–00:45", "PCMS home → contest page → dashboard-এর দ্রুত montage। Title overlay: “Connect PHP to Oracle Database | PCMS”.", "আসসালামু আলাইকুম। আজ আমরা দেখব কীভাবে PHP এবং Oracle Database OCI8 ব্যবহার করে connect করে একটি সম্পূর্ণ PCMS application চালানো যায়। প্রথমে final result দেখাচ্ছি—এই data Oracle থেকে আসছে, এবং participant, organizer ও admin role অনুযায়ী আলাদা dashboard কাজ করছে।", "কোনো credential দেখাবেন না; final result 3–4টি দ্রুত shot।"),
        ("Scene 2 — Learning objectives", "00:45–01:20", "একটি simple slide: PHP → OCI8 → Instant Client → Oracle XE → PCMS.", "এই video শেষে আপনি OCI8 enable করা, Instant Client configure করা, secure .env থেকে connection তৈরি করা, database test চালানো এবং browser-এ complete workflow verify করা শিখবেন।", "শব্দগুলোর উচ্চারণ ধীর ও স্পষ্ট রাখুন।"),
        ("Scene 3 — Project ও architecture পরিচিতি", "01:20–02:40", "VS Code-এ project tree: app, config, database, public, routes, tests।", "PCMS একটি ছোট MVC-style project। public folder হলো web root। Controller request handle করে, service business logic রাখে, repository SQL চালায়, আর app Support Database class একটি shared OCI8 connection দেয়। তাই প্রতিটি model-এ username-password repeat করতে হয় না।", "Explorer zoom করুন; unrelated files collapse করুন।"),
        ("Scene 4 — PHP build যাচাই", "02:40–03:45", "নতুন PowerShell-এ php -v, php --ini, php -i filter command।", "OCI8 install-এর সবচেয়ে গুরুত্বপূর্ণ নিয়ম হলো matching build। PHP version, x64 বা x86, Thread Safe না Non-Thread-Safe এবং compiler family—সব মিলতে হবে। আমার setup PHP 8.5.5 x64 NTS, এবং active configuration C:\\php\\php.ini।", "Output-এর relevant line zoom/cursor দিয়ে দেখান।"),
        ("Scene 5 — OCI8 ও Instant Client", "03:45–05:30", "Instant Client folder এবং PHP ext folder দেখান; download website দেখাতে চাইলে URL/versions only।", "Oracle Instant Client database protocol ও client libraries দেয়, আর PHP OCI8 extension PHP code-কে সেই libraries ব্যবহার করতে দেয়। আমি Instant Client 19.26 একটি versioned folder-এ রেখেছি এবং matching php_oci8_19.dll PHP ext folder-এ রেখেছি।", "Download পুনরায় না করলেও হবে; installed files দেখান।"),
        ("Scene 6 — php.ini edit", "05:30–06:45", "config/php.ini.example খুলুন; তারপর active php.ini-এর শুধু OCI8 lines দেখান।", "এখন active php.ini-তে extension_dir এবং OCI8 extension enable করব। Line-এর শুরুতে semicolon থাকলে সেটি comment, তাই semicolon সরাতে হবে। XAMPP হলে অবশ্যই XAMPP-এর php.ini edit করবেন; CLI এবং Apache ভিন্ন config ব্যবহার করতে পারে।", "Personal paths ছাড়া relevant lines crop/zoom।"),
        ("Scene 7 — PATH ও runtime DLL", "06:45–08:00", "Environment Variables-এর Path entry; তারপর Instant Client files।", "Instant Client folder Windows Path-এ যোগ করলে PHP বা Apache oci.dll খুঁজে পায়। Tutorial-এর মতো Apache ব্যবহার করলে একই Instant Client package থেকে oci.dll, oraociei19.dll এবং oraons.dll Apache bin folder-এ copy করা যায়। Change-এর পরে terminal অথবা Apache restart করতে হবে।", "Path list-এর অন্য sensitive entries blur/crop করুন।"),
        ("Scene 8 — OCI8 verification", "08:00–09:00", "php -m এবং php --ri oci8 run।", "প্রথম success gate হলো PHP সত্যিই OCI8 load করেছে কি না। php -m-এ oci8 এবং php --ri oci8-এ OCI8 Support enabled, extension version ও runtime client version দেখা গেলে extension layer প্রস্তুত।", "Expected তিনটি line স্থিরভাবে 3–4 সেকেন্ড দেখান।"),
        ("Scene 9 — .env, credential safety", "09:00–10:00", ".env.example খুলুন; DB_PASSWORD value placeholder। .gitignore-এ .env entry দেখান।", "Database host, port, service এবং username environment file থেকে আসবে। আসল password কখনো source code, screenshot বা Git repository-তে রাখবেন না। Video-তে আমি শুধু .env.example দেখাচ্ছি; local .env screen-এ খুলছি না।", "Password type বা paste করার scene বাদ দিন।"),
        ("Scene 10 — Database.php explanation", "10:00–12:20", "app/Support/Database.php-তে connection(), descriptor, oci_connect, statement(), bind loop highlight।", "Database class প্রথমে OCI8 extension আছে কি না পরীক্ষা করে। তারপর host, port ও service দিয়ে EZ Connect descriptor বানায় এবং AL32UTF8 charset-এ oci_connect চালায়। Query-এর সময় oci_parse, named parameter binding এবং oci_execute ব্যবহার হয়। Failure হলে exception ওঠে; transaction failure হলে rollback হয়।", "একসঙ্গে 12–15 line-এর বেশি দেখাবেন না; scroll ধীরে।"),
        ("Scene 11 — Repository named bind", "12:20–13:30", "একটি ছোট repository query-তে :status বা :user_id highlight।", "User input SQL string-এর সঙ্গে concatenate করা হবে না। Named bind SQL injection risk কমায় এবং query reusable রাখে। Oracle 10g compatibility-এর জন্য reserved বা ambiguous নাম এড়িয়ে row_start ও row_end-এর মতো bind ব্যবহার করেছি।", "Real user data দেখাবেন না।"),
        ("Scene 12 — Database connection test", "13:30–14:40", "php tests/oracle_connection.php run।", "এখন আসল database connection test চালাচ্ছি। Success message, Oracle banner এবং expected seed counts পাওয়া মানে PHP, OCI8, Instant Client, listener, credential এবং schema—সব layer একসঙ্গে কাজ করছে।", "Success output কাটবেন না; error হলে আগে fix করে re-record।"),
        ("Scene 13 — Smoke ও HTTP tests", "14:40–15:50", "php tests/smoke.php; তারপর tests/live_http.ps1।", "Connection হওয়াই শেষ নয়। Smoke test application logic যাচাই করে, আর live HTTP test public page, তিনটি role dashboard এবং authorization boundary পরীক্ষা করে। এই project-এ 68টি smoke assertion এবং 13টি live HTTP check pass করে।", "Test count স্পষ্টভাবে দেখান।"),
        ("Scene 14 — Application demo", "15:50–18:10", "Browser-এ home, login, participant dashboard; প্রয়োজনে organizer/admin-এর short cuts।", "এখন browser-এ application চালাচ্ছি। Contest data Oracle থেকে load হচ্ছে। Login-এর পরে participant নিজের relevant action দেখে। Organizer contest ও problem manage করতে পারে, এবং admin user ও system-level control পায়। Role mismatch route 403 দেয়—এটাই authorization কাজ করার প্রমাণ।", "Synthetic demo account ব্যবহার; password masked।"),
        ("Scene 15 — Troubleshooting recap", "18:10–19:10", "একটি slide: php.ini → DLL match → PATH → listener → credential।", "যদি connection না হয়, random change না করে পাঁচটি layer পরীক্ষা করুন: active php.ini, matching OCI8 DLL, Instant Client visibility, Oracle listener/service এবং credential/service name। CLI কাজ করে কিন্তু Apache না করলে Apache কোন PHP ও php.ini ব্যবহার করছে তা phpinfo দিয়ে দেখুন।", "phpinfo temporary—শেষে delete বলুন।"),
        ("Scene 16 — Outro", "19:10–19:40", "Working dashboard + closing slide।", "এই ছিল PCMS project-এ PHP থেকে Oracle Database connection-এর complete process। Setup-এর প্রতিটি ধাপ verify করলে error খুব সহজে isolate করা যায়। Video শেষ করার আগে credentials hide আছে কি না এবং temporary phpinfo file delete করা হয়েছে কি না নিশ্চিত করুন। ধন্যবাদ।", "শেষে 3–5 সেকেন্ড clean end screen।"),
    ]

    for idx, (title, timing, screen, narration, note) in enumerate(scenes):
        add_heading(doc, title, 2)
        add_table(doc, ["সময়", "Screen action"], [(timing, screen)], widths=[1.15, 5.45])
        p = doc.add_paragraph()
        p.paragraph_format.space_after = Pt(4)
        r = p.add_run("Narration: ")
        set_run_font(r, size=10, bold=True, color=PURPLE_DARK)
        r = p.add_run(narration)
        set_run_font(r, size=10, color=BLACK)
        p = doc.add_paragraph()
        p.paragraph_format.space_after = Pt(7)
        r = p.add_run("Recording note: ")
        set_run_font(r, size=9, bold=True, color=GREEN)
        r = p.add_run(note)
        set_run_font(r, size=9, color=GRAY)
    add_heading(doc, "১০. Recording ও editing workflow", 1)
    add_heading(doc, "১০.১ Record করার practical sequence", 2)
    add_numbered(doc, [
        "প্রথমে silent screen clips record করুন: commands, code, browser flows।",
        "প্রতিটি scene-এর শুরু ও শেষে 2 সেকেন্ড pause রাখুন—editing handle পাওয়া যাবে।",
        "Script দেখে voiceover আলাদাভাবে record করুন, অথবা scene-by-scene live narration করুন।",
        "Mistake হলে পুরো video restart নয়; sentence pause দিয়ে আবার বলুন, পরে cut করুন।",
        "Cursor movement narration-এর সঙ্গে sync করুন; viewer যে line শুনছে সেই line highlight করুন।",
        "Long loading/wait অংশ cut বা 2× speed করুন; command result hide করবেন না।",
        "Sensitive অঞ্চল crop/blur করুন; credential accidentally capture হলে সেই clip ব্যবহার করবেন না।",
        "Subtitle দিলে technical spelling—OCI8, Instant Client, php.ini, AL32UTF8—manually correct করুন।",
    ])
    add_heading(doc, "১০.২ Suggested on-screen labels", 2)
    add_table(doc, ["Moment", "Overlay text"], [
        ("PHP check", "Step 1 — Confirm PHP build"),
        ("Instant Client", "Step 2 — Install Oracle client libraries"),
        ("php.ini", "Step 3 — Enable OCI8"),
        ("PATH", "Step 4 — Make DLLs discoverable"),
        (".env", "Step 5 — Configure securely"),
        ("Database.php", "Step 6 — Connect with named binds"),
        ("Tests", "Step 7 — Verify before demo"),
        ("Browser", "Final result — PCMS running on Oracle"),
    ], widths=[2.0, 4.6])
    add_heading(doc, "১০.৩ Suggested title ও description", 2)
    add_body(doc, "Video title: PHP to Oracle Database Connection using OCI8 | PCMS Full Project | Bangla Tutorial")
    add_body(doc, "Short description: এই video-তে Windows-এ PHP OCI8, Oracle Instant Client, php.ini, PATH, secure .env configuration, PCMS database adapter, connection tests এবং role-based application demo দেখানো হয়েছে।")
    add_heading(doc, "১১. Final video QA checklist", 1)
    add_table(doc, ["✓", "Final check"], [
        ("□", "Video-তে কোনো real DB password, APP_KEY, private code বা personal email নেই"),
        ("□", "php --ini output এবং active php.ini path পরিষ্কার"),
        ("□", "PHP build matching rule ব্যাখ্যা করা হয়েছে"),
        ("□", "OCI8 Support enabled ও runtime client version দেখানো হয়েছে"),
        ("□", ".env.example দেখানো হয়েছে; .env নয়"),
        ("□", "Database.php-এ descriptor, oci_connect ও named binding দেখানো হয়েছে"),
        ("□", "Connection test সফল output দেখানো হয়েছে"),
        ("□", "Smoke ও live HTTP test result দেখানো হয়েছে"),
        ("□", "Participant, organizer, admin workflow-এর representative demo আছে"),
        ("□", "403 authorization boundary দেখানো হয়েছে"),
        ("□", "Temporary phpinfo file delete করা হয়েছে"),
        ("□", "Audio পরিষ্কার; code readable; cursor movement ধীর"),
        ("□", "Exported MP4 শুরু থেকে শেষ পর্যন্ত একবার দেখা হয়েছে"),
    ], widths=[0.45, 6.15])
    add_heading(doc, "Video-তে কখনো দেখাবেন না", 2)
    add_bullets(doc, [
        "Real .env content বা database password।",
        "Personal email, browser account, notifications বা full PATH list-এর unrelated entries।",
        "Production data, real participant identity বা private invite code।",
        "Unfiltered error page যেখানে full stack trace/paths/credentials থাকতে পারে।",
        "phpinfo page verification-এর পর রেখে দেওয়া।",
    ])
    add_note(doc, "Completion definition", "ভিডিও তখনই complete, যখন viewer document-এর commands অনুসরণ করে OCI8 verify করতে, PCMS connect করতে এবং tests-এর expected evidence মিলাতে পারে।")

    add_page_break(doc)
    add_heading(doc, "Appendix A — Quick command sheet", 1)
    add_code(doc, "# 1. PHP identity\nphp -v\nphp --ini\nphp -i | findstr /I \"Architecture Thread Safety Compiler extension_dir\"\n\n# 2. OCI8\nphp -m | findstr /I oci8\nphp --ri oci8\nwhere.exe oci.dll\n\n# 3. Oracle/network\nGet-Service | Where-Object { $_.Name -match 'Oracle|TNS' }\nnetstat -ano | findstr :1521\n\n# 4. PCMS tests\ncd E:\\advance_database\nphp tests/oracle_connection.php\nphp tests/smoke.php\n\n# 5. Run PCMS\nphp -S 127.0.0.1:8765 -t public public/router.php\n\n# 6. HTTP verification (new terminal)\npowershell -ExecutionPolicy Bypass -File tests/live_http.ps1")
    add_heading(doc, "Appendix B — File map", 1)
    add_table(doc, ["File", "Purpose"], [
        (r"app\Support\Database.php", "Central OCI8 adapter, named binds, fetch, execute, transaction"),
        (r".env.example", "Recording-safe environment variable template"),
        (r"config\php.ini.example", "Reference PHP/OCI8 configuration"),
        (r"docs\oracle-php-connection.md", "Short setup notes matching the tutorial"),
        (r"tests\oracle_connection.php", "Real Oracle connectivity and seed verification"),
        (r"tests\smoke.php", "Application-level smoke assertions"),
        (r"tests\live_http.ps1", "End-to-end role and authorization HTTP checks"),
        (r"public\index.php", "Web front controller"),
        (r"public\.htaccess / public\router.php", "Apache and PHP-server routing"),
    ], widths=[2.45, 4.15])
    add_page_break(doc)
    add_heading(doc, "Appendix C — One-minute recap", 1)
    add_numbered(doc, [
        "PHP build identify করুন।",
        "Matching OCI8 DLL ও Instant Client install করুন।",
        "Active php.ini-তে OCI8 enable করুন।",
        "Instant Client PATH/DLL visibility ঠিক করুন; process restart করুন।",
        "php --ri oci8 দিয়ে extension verify করুন।",
        ".env-এ Oracle host/service/credential দিন—source code-এ নয়।",
        "tests/oracle_connection.php চালান।",
        "PCMS server চালিয়ে smoke ও live HTTP tests pass করুন।",
        "Synthetic data দিয়ে role-based browser demo record করুন।",
        "Credentials, phpinfo এবং personal information final video থেকে remove করুন।",
    ])
    add_body(doc, "— End of guide —")

    # Core properties
    doc.core_properties.title = "PCMS PHP to Oracle Connection and Video Guide"
    doc.core_properties.subject = "Bangla setup, verification, and tutorial video script"
    doc.core_properties.author = "PCMS Project"
    doc.core_properties.keywords = "PCMS, PHP, Oracle, OCI8, Instant Client, XAMPP, Bangla tutorial"
    doc.save(OUT)
    print(OUT)


if __name__ == "__main__":
    build()
