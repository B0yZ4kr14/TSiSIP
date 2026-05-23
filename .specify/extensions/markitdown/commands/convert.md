---
description: "Convert a document to Markdown and place it in the spec directory as reference material"
---

# Document to Markdown Conversion

You are converting a document into Markdown format using the `markitdown` CLI tool.
The resulting Markdown file will be placed in the spec directory as reference material
for Spec Kit workflows (specify, plan, tasks, implement).

Follow each step sequentially. Do not skip steps.

---

## Step 0 — Pre-flight Checks

Run these checks before proceeding. Stop and report if any check fails.

### 0a. Verify markitdown is installed

Run the following command to check if markitdown is available:

```bash
markitdown --version
```

If this fails, stop and tell the user:
> **markitdown is not installed.** Install it with:
> ```
> pip install 'markitdown[all]'
> ```
> Or install only the formats you need:
> ```
> pip install 'markitdown[pdf,docx,pptx,xlsx]'
> ```
> Then retry this command.

### 0b. Verify Python is available

markitdown requires Python 3.10+. Verify:

```bash
python --version
```

If Python is not available or the version is below 3.10, inform the user.

---

## Step 1 — Resolve the Input File

The user may have provided a file path inline with the command invocation. Parse the user's input for:

| Parameter | Description |
|-----------|-------------|
| `file` | Path to the document to convert (PDF, DOCX, PPTX, XLSX, HTML, CSV, JSON, etc.) |
| `output` | (Optional) Custom output path for the Markdown file |

### 1a. Resolve the file path

**If a file path is provided:** Verify the file exists and is accessible.

**If no file path is provided:** Ask the user:
> Which document would you like to convert to Markdown? Provide the file path.

### 1b. Validate the file type

markitdown supports the following formats:
- **Tier 1 (core):** PDF (`.pdf`), Word (`.docx`), PowerPoint (`.pptx`), Excel (`.xlsx`)
- **Tier 2 (extended):** HTML (`.html`), CSV (`.csv`), JSON (`.json`), XML (`.xml`), Images (`.jpg`, `.png` — EXIF/OCR), Audio (`.mp3`, `.wav`), EPub (`.epub`), ZIP (`.zip`)

If the file extension is not in the supported list, warn the user but attempt conversion anyway — markitdown may still handle it.

### 1c. Resolve the output path

**If `output` is provided:** Use it directly.

**If not provided:** Use the default output location in priority order:
1. `.specify/specs/<filename-without-extension>.md` (if a `.specify/` directory exists)
2. Same directory as the input file: `<input-dir>/<filename-without-extension>.md`

### 1d. Confirm with the user

Display the resolved parameters:
> **Input:** `<file path>`
> **Format:** `<detected format>`
> **Output:** `<output path>`
>
> Proceed with conversion?

Wait for the user to confirm before continuing to Step 2.

---

## Step 2 — Convert the Document

### 2a. Run markitdown

Execute the conversion:

```bash
markitdown "<file-path>" -o "<output-path>"
```

### 2b. Handle conversion errors

If the command fails:
- **Missing dependencies for format:** Suggest installing the specific optional dependency (e.g., `pip install 'markitdown[pdf]'` for PDF files)
- **File not found:** Verify the path and ask the user to correct it
- **Permission denied:** Inform the user about file access issues
- **Unsupported format:** Inform the user that the file type is not supported by markitdown

### 2c. Verify output

Check that the output file was created and is non-empty. Read the first few lines to confirm it contains valid Markdown content.

---

## Step 3 — Post-processing

### 3a. Add metadata header

Prepend a metadata block to the generated Markdown file:

```markdown
---
Source:
  type: document-conversion
  originalFile: "<original filename>"
  originalFormat: "<format>"
  convertedAt: "<current timestamp>"
  converter: "markitdown"
  converterVersion: "<markitdown version>"
---
```

### 3b. Review output quality

Briefly scan the converted content for:
- Empty output (conversion may have failed silently)
- Garbled text (may indicate wrong format detection or encoding issues)
- Missing content (tables, images, headers may not have converted)

If issues are detected, flag them to the user with suggestions:
- For PDFs with poor text extraction: suggest Azure Document Intelligence (`markitdown -d -e "<endpoint>"`)
- For documents with images: suggest the markitdown-ocr plugin for OCR support

---

## Step 4 — Output Summary

After generating the Markdown file, display a summary:

```
✅ Document converted successfully

📄 Output: <output path>
📊 Conversion Summary:
   • Source: <original filename> (<format>)
   • Output size: <file size>
   • Converter: markitdown <version>

💡 Next Steps:
   • Reference this file in your spec: `See [<filename>](.specify/specs/<filename>.md)`
   • Run /speckit.specify to create a specification using this document as input
   • Run /speckit.markitdown.convert again for additional documents
```

---

## Error Handling

- **markitdown not installed:** Direct user to install with `pip install 'markitdown[all]'`
- **Python not found:** Direct user to install Python 3.10+
- **File not found:** Ask user to verify the path
- **Empty output:** Suggest trying Azure Document Intelligence for better PDF extraction
- **Permission denied:** Explain file access requirements

---

## Notes

- This command uses the markitdown CLI (not the Python API) for simplicity and to keep the extension dependency-light.
- markitdown uses `convert_local()` internally for local files — this is the secure path that avoids remote URI fetching.
- The converted Markdown is a reference artifact — it drops into the spec directory alongside other spec files. The user references it in their prompts during `/speckit.specify` or `/speckit.plan` steps.
- For higher-quality PDF conversion, users can set up Azure Document Intelligence and use the `-d` flag.
- The markitdown-ocr plugin can be installed separately for OCR support on embedded images.
