# Project Rules — Santai AI Growth OS

## Prinsip Utama

Santai AI Growth OS dibina untuk membantu Santai Ilmu Publication:
- meningkatkan jualan,
- menjimatkan masa operasi,
- menyusun kandungan dan kempen,
- mengukur prestasi pemasaran.

## Peraturan Pembangunan

1. Satu sprint hanya mempunyai satu objektif utama.
2. Tiada feature baharu sebelum feature semasa stabil.
3. Semua pembangunan dibuat dalam branch khusus.
4. Branch `main` mesti sentiasa stabil dan boleh digunakan.
5. ZIP hanya dibina untuk Release Candidate atau Stable Release.
6. Semua connector mesti mempunyai connection settings, test connection, health status, API logs dan error handling.
7. Jangan simpan access token, API key atau App Secret dalam GitHub.
8. Semua credential mesti disimpan dalam WordPress options atau environment configuration.
9. Semua perubahan mesti mempunyai commit message yang jelas.
10. Jangan ubah architecture tanpa sebab teknikal yang kukuh.

## Workflow

```text
Issue
↓
Branch
↓
Development
↓
LocalWP Test
↓
Commit
↓
Push
↓
Review
↓
Merge
↓
Release
```

## Definition of Done

Sesuatu feature dianggap siap apabila:
- tiada fatal error,
- lulus ujian LocalWP,
- mesej ralat jelas,
- data tersimpan dengan betul,
- tidak merosakkan modul sedia ada,
- dokumentasi dikemas kini.
