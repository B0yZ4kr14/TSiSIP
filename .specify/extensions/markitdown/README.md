# spec-kit-markitdown

**Document to Markdown Conversion for [Spec Kit](https://github.com/github/spec-kit)** — Convert documents (PDF, Word, PowerPoint, Excel, and more) to LLM-friendly Markdown for use as reference material in Spec Kit workflows.

## What It Does

This extension provides a command that converts documents into Markdown using Microsoft's [markitdown](https://github.com/microsoft/markitdown) CLI, placing the output directly into your spec directory as reference material.

| Command | Description |
|---------|-------------|
| `/speckit.markitdown.convert` | Convert a document (PDF, Word, PowerPoint, Excel, etc.) to Markdown and place it in the spec directory as reference material |

### Key Features

- **Broad format support** — PDF, Word, PowerPoint, Excel, HTML, CSV, JSON, XML, images, audio, EPub, and ZIP
- **Spec directory integration** — Output defaults to `.specify/specs/` so converted files are immediately available in your spec workflow
- **Source metadata headers** — Every converted file includes YAML frontmatter linking back to the original document
- **Output quality checks** — Flags empty output, garbled text, or missing content with actionable suggestions
- **Azure Document Intelligence support** — For higher-quality PDF extraction, users can leverage Azure AI

---

## Prerequisites

### 1. markitdown CLI

Install Microsoft's markitdown CLI:

```bash
pip install 'markitdown[all]'
```

Or install only the formats you need:

```bash
pip install 'markitdown[pdf,docx,pptx,xlsx]'
```

Verify the installation:

```bash
markitdown --version
```

### 2. Python 3.10+

markitdown requires Python 3.10 or later:

```bash
python --version
```

### 3. Spec Kit

This extension requires [Spec Kit](https://github.com/github/spec-kit) v0.1.0 or later.

---

## Installation

### From the community catalog

```bash
specify extension add markitdown
```

### From a local path (for development/testing)

```bash
specify extension add ./path/to/spec-kit-markitdown
```

---

## Usage

### Basic usage

```
/speckit.markitdown.convert file="requirements.pdf"
```

The command will:
1. Verify markitdown is installed
2. Validate the file exists and detect its format
3. Convert the document to Markdown
4. Add source metadata headers
5. Place the output in `.specify/specs/`

### With custom output path

```
/speckit.markitdown.convert file="design.pptx" output="docs/design-notes.md"
```

### Interactive mode

```
/speckit.markitdown.convert
```

If no file is provided, the command will prompt you to specify the document path.

### Supported Formats

| Tier | Formats | Extensions |
|------|---------|------------|
| **Core** | PDF, Word, PowerPoint, Excel | `.pdf`, `.docx`, `.pptx`, `.xlsx` |
| **Extended** | HTML, CSV, JSON, XML | `.html`, `.csv`, `.json`, `.xml` |
| **Extended** | Images (EXIF/OCR) | `.jpg`, `.png` |
| **Extended** | Audio (EXIF/transcription) | `.mp3`, `.wav` |
| **Extended** | Other | `.epub`, `.zip` |

---

## Output

### File location

By default, converted files are placed at:
- `.specify/specs/<filename>.md` — if a `.specify/` directory exists
- Same directory as the input file — otherwise

### Metadata format

Each converted file includes a YAML frontmatter header:

```yaml
---
Source:
  type: document-conversion
  originalFile: "requirements.pdf"
  originalFormat: "pdf"
  convertedAt: "2026-04-23T10:30:00Z"
  converter: "markitdown"
  converterVersion: "0.1.0"
---
```

### Next steps after conversion

The converted Markdown is a reference artifact. Use it in your Spec Kit workflow:

1. **Review** the converted content for accuracy
2. `/speckit.specify` — Create a specification using the converted document as input
3. `/speckit.plan` — Create a technical implementation plan
4. `/speckit.tasks` — Generate actionable task breakdown
5. `/speckit.implement` — Execute the implementation

---

## Examples

See the [docs/examples/](docs/examples/) directory for sample outputs:

- [Convert example](docs/examples/convert-example.md) — sample PDF → Markdown conversion with metadata

---

## Known Limitations

- **PDF quality varies** — Scanned PDFs or image-heavy PDFs may produce poor text output. For better results, use Azure Document Intelligence (`markitdown -d -e "<endpoint>"`)
- **Image-heavy documents** — Embedded images are not converted to text by default. Install the [markitdown-ocr](https://github.com/microsoft/markitdown) plugin for OCR support
- **Large files may be slow** — Very large documents (100+ pages) may take significant time to process
- **Format detection** — markitdown relies on file extensions for format detection. Misnamed files may not convert correctly

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| `markitdown: command not found` | Install with `pip install 'markitdown[all]'` |
| `Python version too old` | Upgrade to Python 3.10+ |
| `Empty output file` | Try Azure Document Intelligence for PDFs: `markitdown -d -e "<endpoint>"` |
| `Missing dependency for format` | Install the specific extra: `pip install 'markitdown[pdf]'` |
| `Permission denied` | Check file read permissions on the source document |
| `Garbled text output` | The file may have encoding issues or be a scanned image — try OCR plugin |

---

## Development

### Project structure

```
spec-kit-markitdown/
├── extension.yml           # Extension manifest
├── commands/
│   └── convert.md          # Convert command (AI agent instructions)
├── docs/examples/
│   └── convert-example.md  # Example conversion output
├── README.md
├── CHANGELOG.md
└── LICENSE
```

### Testing locally

1. Clone this repository
2. Install in a test project: `specify extension add ./path/to/spec-kit-markitdown`
3. Ensure markitdown is installed: `pip install 'markitdown[all]'`
4. Run `/speckit.markitdown.convert` against a test document

---

## License

[MIT](LICENSE)
