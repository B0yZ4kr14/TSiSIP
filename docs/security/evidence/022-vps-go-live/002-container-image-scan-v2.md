# Container Image Scan Evidence v2 (G6) — Post-Patch

**Date**: 2026-05-23T23:25:22-03:00
**Tool**: Trivy Version: 0.70.0
**Images**: Locally rebuilt with apt-get upgrade / apk upgrade

## Scanning: tsisip/opensips:test
```

Report Summary

┌─────────────────────────────────────┬────────┬─────────────────┬─────────┐
│               Target                │  Type  │ Vulnerabilities │ Secrets │
├─────────────────────────────────────┼────────┼─────────────────┼─────────┤
│ tsisip/opensips:test (debian 12.14) │ debian │       10        │    -    │
└─────────────────────────────────────┴────────┴─────────────────┴─────────┘
Legend:
- '-': Not scanned
- '0': Clean (no security findings detected)


tsisip/opensips:test (debian 12.14)
===================================
Total: 10 (HIGH: 9, CRITICAL: 1)

┌──────────────────┬────────────────┬──────────┬──────────────┬───────────────────┬───────────────┬─────────────────────────────────────────────────────────────┐
│     Library      │ Vulnerability  │ Severity │    Status    │ Installed Version │ Fixed Version │                            Title                            │
├──────────────────┼────────────────┼──────────┼──────────────┼───────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ libgssapi-krb5-2 │ CVE-2026-40356 │ HIGH     │ affected     │ 1.20.1-2+deb12u5  │               │ krb5: MIT Kerberos 5 (krb5): Denial of Service via integer  │
│                  │                │          │              │                   │               │ underflow and...                                            │
│                  │                │          │              │                   │               │ https://avd.aquasec.com/nvd/cve-2026-40356                  │
├──────────────────┤                │          │              │                   ├───────────────┤                                                             │
│ libk5crypto3     │                │          │              │                   │               │                                                             │
│                  │                │          │              │                   │               │                                                             │
│                  │                │          │              │                   │               │                                                             │
├──────────────────┤                │          │              │                   ├───────────────┤                                                             │
│ libkrb5-3        │                │          │              │                   │               │                                                             │
│                  │                │          │              │                   │               │                                                             │
│                  │                │          │              │                   │               │                                                             │
├──────────────────┤                │          │              │                   ├───────────────┤                                                             │
│ libkrb5support0  │                │          │              │                   │               │                                                             │
│                  │                │          │              │                   │               │                                                             │
│                  │                │          │              │                   │               │                                                             │
├──────────────────┼────────────────┤          │              ├───────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ libldap-2.5-0    │ CVE-2023-2953  │          │              │ 2.5.13+dfsg-5     │               │ openldap: null pointer dereference in ber_memalloc_x        │
│                  │                │          │              │                   │               │ function                                                    │
│                  │                │          │              │                   │               │ https://avd.aquasec.com/nvd/cve-2023-2953                   │
├──────────────────┼────────────────┤          │              ├───────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ libncursesw6     │ CVE-2025-69720 │          │              │ 6.4-4             │               │ ncurses: ncurses: Buffer overflow vulnerability may lead to │
│                  │                │          │              │                   │               │ arbitrary code execution.                                   │
│                  │                │          │              │                   │               │ https://avd.aquasec.com/nvd/cve-2025-69720                  │
├──────────────────┤                │          │              │                   ├───────────────┤                                                             │
│ libtinfo6        │                │          │              │                   │               │                                                             │
│                  │                │          │              │                   │               │                                                             │
│                  │                │          │              │                   │               │                                                             │
├──────────────────┤                │          │              │                   ├───────────────┤                                                             │
│ ncurses-base     │                │          │              │                   │               │                                                             │
│                  │                │          │              │                   │               │                                                             │
│                  │                │          │              │                   │               │                                                             │
├──────────────────┤                │          │              │                   ├───────────────┤                                                             │
│ ncurses-bin      │                │          │              │                   │               │                                                             │
│                  │                │          │              │                   │               │                                                             │
│                  │                │          │              │                   │               │                                                             │
├──────────────────┼────────────────┼──────────┼──────────────┼───────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ zlib1g           │ CVE-2023-45853 │ CRITICAL │ will_not_fix │ 1:1.2.13.dfsg-1   │               │ zlib: integer overflow and resultant heap-based buffer      │
│                  │                │          │              │                   │               │ overflow in zipOpenNewFileInZip4_6                          │
│                  │                │          │              │                   │               │ https://avd.aquasec.com/nvd/cve-2023-45853                  │
└──────────────────┴────────────────┴──────────┴──────────────┴───────────────────┴───────────────┴─────────────────────────────────────────────────────────────┘
```

- tsisip/opensips:test: CRITICAL=1, HIGH=9

## Scanning: tsisip/postgres:test
```

Report Summary

┌────────────────────────────────────┬──────────┬─────────────────┬─────────┐
│               Target               │   Type   │ Vulnerabilities │ Secrets │
├────────────────────────────────────┼──────────┼─────────────────┼─────────┤
│ tsisip/postgres:test (debian 13.5) │  debian  │       16        │    -    │
├────────────────────────────────────┼──────────┼─────────────────┼─────────┤
│ usr/local/bin/gosu                 │ gobinary │       12        │    -    │
└────────────────────────────────────┴──────────┴─────────────────┴─────────┘
Legend:
- '-': Not scanned
- '0': Clean (no security findings detected)


tsisip/postgres:test (debian 13.5)
==================================
Total: 16 (HIGH: 16, CRITICAL: 0)

┌──────────────────┬────────────────┬──────────┬──────────┬──────────────────────────────────────┬───────────────┬─────────────────────────────────────────────────────────────┐
│     Library      │ Vulnerability  │ Severity │  Status  │          Installed Version           │ Fixed Version │                            Title                            │
├──────────────────┼────────────────┼──────────┼──────────┼──────────────────────────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ dirmngr          │ CVE-2026-24882 │ HIGH     │ affected │ 2.4.7-21+deb13u1+b3                  │               │ GnuPG: GnuPG: Stack-based buffer overflow in tpm2daemon     │
│                  │                │          │          │                                      │               │ allows arbitrary code execution                             │
│                  │                │          │          │                                      │               │ https://avd.aquasec.com/nvd/cve-2026-24882                  │
├──────────────────┤                │          │          ├──────────────────────────────────────┼───────────────┤                                                             │
│ gnupg            │                │          │          │ 2.4.7-21+deb13u1                     │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┤                │          │          │                                      ├───────────────┤                                                             │
│ gnupg-l10n       │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┤                │          │          ├──────────────────────────────────────┼───────────────┤                                                             │
│ gpg              │                │          │          │ 2.4.7-21+deb13u1+b3                  │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┤                │          │          │                                      ├───────────────┤                                                             │
│ gpg-agent        │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┤                │          │          │                                      ├───────────────┤                                                             │
│ gpgconf          │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┤                │          │          │                                      ├───────────────┤                                                             │
│ gpgsm            │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┼────────────────┤          │          ├──────────────────────────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ libgssapi-krb5-2 │ CVE-2026-40356 │          │          │ 1.21.3-5+deb13u1                     │               │ krb5: MIT Kerberos 5 (krb5): Denial of Service via integer  │
│                  │                │          │          │                                      │               │ underflow and...                                            │
│                  │                │          │          │                                      │               │ https://avd.aquasec.com/nvd/cve-2026-40356                  │
├──────────────────┤                │          │          │                                      ├───────────────┤                                                             │
│ libk5crypto3     │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┤                │          │          │                                      ├───────────────┤                                                             │
│ libkrb5-3        │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┤                │          │          │                                      ├───────────────┤                                                             │
│ libkrb5support0  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┼────────────────┤          │          ├──────────────────────────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ libncursesw6     │ CVE-2025-69720 │          │          │ 6.5+20250216-2                       │               │ ncurses: ncurses: Buffer overflow vulnerability may lead to │
│                  │                │          │          │                                      │               │ arbitrary code execution.                                   │
│                  │                │          │          │                                      │               │ https://avd.aquasec.com/nvd/cve-2025-69720                  │
├──────────────────┤                │          │          │                                      ├───────────────┤                                                             │
│ libtinfo6        │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┼────────────────┤          │          ├──────────────────────────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ libxml2          │ CVE-2026-6732  │          │          │ 2.12.7+dfsg+really2.9.14-2.1+deb13u2 │               │ libxml2: libxml2: Denial of Service via crafted             │
│                  │                │          │          │                                      │               │ XSD-validated document                                      │
│                  │                │          │          │                                      │               │ https://avd.aquasec.com/nvd/cve-2026-6732                   │
├──────────────────┼────────────────┤          │          ├──────────────────────────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ ncurses-base     │ CVE-2025-69720 │          │          │ 6.5+20250216-2                       │               │ ncurses: ncurses: Buffer overflow vulnerability may lead to │
│                  │                │          │          │                                      │               │ arbitrary code execution.                                   │
│                  │                │          │          │                                      │               │ https://avd.aquasec.com/nvd/cve-2025-69720                  │
├──────────────────┤                │          │          │                                      ├───────────────┤                                                             │
│ ncurses-bin      │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
└──────────────────┴────────────────┴──────────┴──────────┴──────────────────────────────────────┴───────────────┴─────────────────────────────────────────────────────────────┘

usr/local/bin/gosu (gobinary)
=============================
Total: 12 (HIGH: 11, CRITICAL: 1)

┌─────────┬────────────────┬──────────┬────────┬───────────────────┬──────────────────────────────┬──────────────────────────────────────────────────────────────┐
│ Library │ Vulnerability  │ Severity │ Status │ Installed Version │        Fixed Version         │                            Title                             │
├─────────┼────────────────┼──────────┼────────┼───────────────────┼──────────────────────────────┼──────────────────────────────────────────────────────────────┤
│ stdlib  │ CVE-2025-68121 │ CRITICAL │ fixed  │ v1.24.6           │ 1.24.13, 1.25.7, 1.26.0-rc.3 │ crypto/tls: crypto/tls: Incorrect certificate validation     │
│         │                │          │        │                   │                              │ during TLS session resumption                                │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2025-68121                   │
│         ├────────────────┼──────────┤        │                   ├──────────────────────────────┼──────────────────────────────────────────────────────────────┤
│         │ CVE-2025-61726 │ HIGH     │        │                   │ 1.24.12, 1.25.6              │ golang: net/url: Memory exhaustion in query parameter        │
│         │                │          │        │                   │                              │ parsing in net/url                                           │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2025-61726                   │
│         ├────────────────┤          │        │                   ├──────────────────────────────┼──────────────────────────────────────────────────────────────┤
│         │ CVE-2025-61729 │          │        │                   │ 1.24.11, 1.25.5              │ crypto/x509: golang: Denial of Service due to excessive      │
│         │                │          │        │                   │                              │ resource consumption via crafted...                          │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2025-61729                   │
│         ├────────────────┤          │        │                   ├──────────────────────────────┼──────────────────────────────────────────────────────────────┤
│         │ CVE-2026-25679 │          │        │                   │ 1.25.8, 1.26.1               │ net/url: Incorrect parsing of IPv6 host literals in net/url  │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2026-25679                   │
│         ├────────────────┤          │        │                   ├──────────────────────────────┼──────────────────────────────────────────────────────────────┤
│         │ CVE-2026-32280 │          │        │                   │ 1.25.9, 1.26.2               │ crypto/x509: crypto/tls: golang: Go: Denial of Service       │
│         │                │          │        │                   │                              │ vulnerability in certificate chain building...               │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2026-32280                   │
│         ├────────────────┤          │        │                   │                              ├──────────────────────────────────────────────────────────────┤
│         │ CVE-2026-32281 │          │        │                   │                              │ crypto/x509: golang: Go crypto/x509: Denial of Service via   │
│         │                │          │        │                   │                              │ inefficient certificate chain validation...                  │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2026-32281                   │
│         ├────────────────┤          │        │                   │                              ├──────────────────────────────────────────────────────────────┤
│         │ CVE-2026-32283 │          │        │                   │                              │ crypto/tls: golang: Go crypto/tls: Denial of Service via     │
│         │                │          │        │                   │                              │ multiple TLS 1.3 key...                                      │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2026-32283                   │
│         ├────────────────┤          │        │                   ├──────────────────────────────┼──────────────────────────────────────────────────────────────┤
│         │ CVE-2026-33811 │          │        │                   │ 1.25.10, 1.26.3              │ When using LookupCNAME with the cgo DNS resolver, a very     │
│         │                │          │        │                   │                              │ long CNAME...                                                │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2026-33811                   │
│         ├────────────────┤          │        │                   │                              ├──────────────────────────────────────────────────────────────┤
│         │ CVE-2026-33814 │          │        │                   │                              │ When processing HTTP/2 SETTINGS frames, transport will enter │
│         │                │          │        │                   │                              │ an infini ...                                                │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2026-33814                   │
│         ├────────────────┤          │        │                   │                              ├──────────────────────────────────────────────────────────────┤
│         │ CVE-2026-39820 │          │        │                   │                              │ Well-crafted inputs reaching ParseAddress, ParseAddressList, │
│         │                │          │        │                   │                              │ and Parse ...                                                │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2026-39820                   │
│         ├────────────────┤          │        │                   │                              ├──────────────────────────────────────────────────────────────┤
│         │ CVE-2026-39836 │          │        │                   │                              │ Panic in Dial and LookupPort when handling NUL byte on       │
│         │                │          │        │                   │                              │ Windows in...                                                │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2026-39836                   │
│         ├────────────────┤          │        │                   │                              ├──────────────────────────────────────────────────────────────┤
│         │ CVE-2026-42499 │          │        │                   │                              │ Pathological inputs could cause DoS through consumePhrase    │
│         │                │          │        │                   │                              │ when parsing ...                                             │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2026-42499                   │
└─────────┴────────────────┴──────────┴────────┴───────────────────┴──────────────────────────────┴──────────────────────────────────────────────────────────────┘
```

- tsisip/postgres:test: CRITICAL=1, HIGH=27

## Scanning: tsisip/rtpengine:test
```

Report Summary

┌──────────────────────────────────────┬────────┬─────────────────┬─────────┐
│                Target                │  Type  │ Vulnerabilities │ Secrets │
├──────────────────────────────────────┼────────┼─────────────────┼─────────┤
│ tsisip/rtpengine:test (debian 12.14) │ debian │       50        │    -    │
└──────────────────────────────────────┴────────┴─────────────────┴─────────┘
Legend:
- '-': Not scanned
- '0': Clean (no security findings detected)


tsisip/rtpengine:test (debian 12.14)
====================================
Total: 50 (HIGH: 40, CRITICAL: 10)

┌───────────────────────┬────────────────┬──────────┬──────────────┬───────────────────────────┬───────────────┬──────────────────────────────────────────────────────────────┐
│        Library        │ Vulnerability  │ Severity │    Status    │     Installed Version     │ Fixed Version │                            Title                             │
├───────────────────────┼────────────────┼──────────┼──────────────┼───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libaom3               │ CVE-2023-6879  │ CRITICAL │ affected     │ 3.6.0-1+deb12u2           │               │ aom: heap-buffer-overflow on frame size change               │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2023-6879                    │
│                       ├────────────────┼──────────┼──────────────┤                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2023-39616 │ HIGH     │ will_not_fix │                           │               │ AOMedia v3.0.0 to v3.5.0 was discovered to contain an        │
│                       │                │          │              │                           │               │ invalid read mem...                                          │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2023-39616                   │
├───────────────────────┼────────────────┤          ├──────────────┼───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libcurl4              │ CVE-2026-5773  │          │ affected     │ 7.88.1-10+deb12u14        │               │ curl: libcurl: Wrong file transfer due to incorrect SMB      │
│                       │                │          │              │                           │               │ connection reuse                                             │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-5773                    │
│                       ├────────────────┤          │              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2026-6276  │          │              │                           │               │ curl: libcurl: Information disclosure due to cookie leak     │
│                       │                │          │              │                           │               │ when reusing connections with...                             │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-6276                    │
├───────────────────────┼────────────────┤          ├──────────────┼───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libexpat1             │ CVE-2025-59375 │          │ will_not_fix │ 2.5.0-1+deb12u2           │               │ firefox: thunderbird: expat: libexpat in Expat allows        │
│                       │                │          │              │                           │               │ attackers to trigger large dynamic...                        │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2025-59375                   │
│                       ├────────────────┤          ├──────────────┤                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2026-25210 │          │ affected     │                           │               │ libexpat: libexpat: Information disclosure and data          │
│                       │                │          │              │                           │               │ integrity issues due to integer overflow...                  │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-25210                   │
│                       ├────────────────┤          │              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2026-45186 │          │              │                           │               │ libexpat: denial of service via crafted XML input            │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-45186                   │
├───────────────────────┼────────────────┤          │              ├───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libgssapi-krb5-2      │ CVE-2026-40356 │          │              │ 1.20.1-2+deb12u5          │               │ krb5: MIT Kerberos 5 (krb5): Denial of Service via integer   │
│                       │                │          │              │                           │               │ underflow and...                                             │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-40356                   │
├───────────────────────┼────────────────┤          │              ├───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libharfbuzz0b         │ CVE-2023-25193 │          │              │ 6.0.0+dfsg-3              │               │ harfbuzz: allows attackers to trigger O(n^2) growth via      │
│                       │                │          │              │                           │               │ consecutive marks                                            │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2023-25193                   │
├───────────────────────┼────────────────┤          │              ├───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libk5crypto3          │ CVE-2026-40356 │          │              │ 1.20.1-2+deb12u5          │               │ krb5: MIT Kerberos 5 (krb5): Denial of Service via integer   │
│                       │                │          │              │                           │               │ underflow and...                                             │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-40356                   │
├───────────────────────┤                │          │              │                           ├───────────────┤                                                              │
│ libkrb5-3             │                │          │              │                           │               │                                                              │
│                       │                │          │              │                           │               │                                                              │
│                       │                │          │              │                           │               │                                                              │
├───────────────────────┤                │          │              │                           ├───────────────┤                                                              │
│ libkrb5support0       │                │          │              │                           │               │                                                              │
│                       │                │          │              │                           │               │                                                              │
│                       │                │          │              │                           │               │                                                              │
├───────────────────────┼────────────────┤          │              ├───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libldap-2.5-0         │ CVE-2023-2953  │          │              │ 2.5.13+dfsg-5             │               │ openldap: null pointer dereference in ber_memalloc_x         │
│                       │                │          │              │                           │               │ function                                                     │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2023-2953                    │
├───────────────────────┼────────────────┤          │              ├───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libmariadb3           │ CVE-2025-13699 │          │              │ 1:10.11.14-0+deb12u2      │               │ mariadb: MariaDB: mariadb-dump utility vulnerable to remote  │
│                       │                │          │              │                           │               │ code execution via improper path...                          │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2025-13699                   │
├───────────────────────┼────────────────┼──────────┤              ├───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libmbedcrypto7        │ CVE-2025-47917 │ CRITICAL │              │ 2.28.3-1                  │               │ Mbed TLS before 3.6.4 allows a use-after-free in certain     │
│                       │                │          │              │                           │               │ situations of ......                                         │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2025-47917                   │
│                       ├────────────────┤          │              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2026-34873 │          │              │                           │               │ mbedtls: Mbed TLS: Client impersonation during TLS 1.3       │
│                       │                │          │              │                           │               │ session resumption                                           │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-34873                   │
│                       ├────────────────┤          │              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2026-34875 │          │              │                           │               │ mbedtls: Mbed TLS and TF-PSA-Crypto: Arbitrary code          │
│                       │                │          │              │                           │               │ execution due to buffer overflow...                          │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-34875                   │
│                       ├────────────────┼──────────┤              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2024-23775 │ HIGH     │              │                           │               │ Integer Overflow vulnerability in Mbed TLS 2.x before 2.28.7 │
│                       │                │          │              │                           │               │ and 3.x b...                                                 │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2024-23775                   │
│                       ├────────────────┤          │              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2025-48965 │          │              │                           │               │ Mbed TLS before 3.6.4 has a NULL pointer dereference because │
│                       │                │          │              │                           │               │ mbedtls_a ......                                             │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2025-48965                   │
│                       ├────────────────┤          │              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2025-52496 │          │              │                           │               │ Mbed TLS before 3.6.4 has a race condition in AESNI          │
│                       │                │          │              │                           │               │ detection if...                                              │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2025-52496                   │
│                       ├────────────────┤          │              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2026-25835 │          │              │                           │               │ Mbed TLS before 3.6.6 and TF-PSA-Crypto before 1.1.0 misuse  │
│                       │                │          │              │                           │               │ seeds in a...                                                │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-25835                   │
│                       ├────────────────┤          │              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2026-34872 │          │              │                           │               │ mbedtls: Mbed TLS and TF-PSA-Crypto: Shared secret           │
│                       │                │          │              │                           │               │ manipulation via improper FFDH input...                      │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-34872                   │
├───────────────────────┼────────────────┤          ├──────────────┼───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libmfx1               │ CVE-2023-45221 │          │ will_not_fix │ 22.5.4-1                  │               │ Improper buffer restrictions in Intel(R) Media SDK all       │
│                       │                │          │              │                           │               │ versions may al ......                                       │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2023-45221                   │
├───────────────────────┼────────────────┤          ├──────────────┼───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libncursesw6          │ CVE-2025-69720 │          │ affected     │ 6.4-4                     │               │ ncurses: ncurses: Buffer overflow vulnerability may lead to  │
│                       │                │          │              │                           │               │ arbitrary code execution.                                    │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2025-69720                   │
├───────────────────────┼────────────────┼──────────┤              ├───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libpython3.11-minimal │ CVE-2026-7210  │ CRITICAL │              │ 3.11.2-6+deb12u7          │               │ `xml.parsers.expat` and `xml.etree.ElementTree` use          │
│                       │                │          │              │                           │               │ insufficient entro ...                                       │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-7210                    │
│                       ├────────────────┼──────────┤              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2025-69534 │ HIGH     │              │                           │               │ python-markdown: denial of service via malformed HTML-like   │
│                       │                │          │              │                           │               │ sequences                                                    │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2025-69534                   │
│                       ├────────────────┤          │              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2026-6100  │          │              │                           │               │ python: Python: Arbitrary code execution or information      │
│                       │                │          │              │                           │               │ disclosure via use-after-free in decompression...            │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-6100                    │
├───────────────────────┼────────────────┼──────────┤              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│ libpython3.11-stdlib  │ CVE-2026-7210  │ CRITICAL │              │                           │               │ `xml.parsers.expat` and `xml.etree.ElementTree` use          │
│                       │                │          │              │                           │               │ insufficient entro ...                                       │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-7210                    │
│                       ├────────────────┼──────────┤              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2025-69534 │ HIGH     │              │                           │               │ python-markdown: denial of service via malformed HTML-like   │
│                       │                │          │              │                           │               │ sequences                                                    │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2025-69534                   │
│                       ├────────────────┤          │              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2026-6100  │          │              │                           │               │ python: Python: Arbitrary code execution or information      │
│                       │                │          │              │                           │               │ disclosure via use-after-free in decompression...            │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-6100                    │
├───────────────────────┼────────────────┤          ├──────────────┼───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libsndfile1           │ CVE-2026-37555 │          │ fix_deferred │ 1.2.0-1+deb12u1           │               │ libsndfile: integer overflow in ima_reader_init()            │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-37555                   │
├───────────────────────┼────────────────┼──────────┼──────────────┼───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libsqlite3-0          │ CVE-2025-7458  │ CRITICAL │ affected     │ 3.40.1-2+deb12u2          │               │ sqlite: SQLite integer overflow                              │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2025-7458                    │
├───────────────────────┼────────────────┼──────────┤              ├───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libssh-gcrypt-4       │ CVE-2026-0966  │ HIGH     │              │ 0.10.6-0+deb12u2          │               │ libssh: libssh: Denial of Service via zero-length input in   │
│                       │                │          │              │                           │               │ ssh_get_hexa()                                               │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-0966                    │
│                       ├────────────────┤          │              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2026-3731  │          │              │                           │               │ libssh: libssh: Denial of Service via out-of-bounds read in  │
│                       │                │          │              │                           │               │ SFTP extension name...                                       │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-3731                    │
├───────────────────────┼────────────────┤          │              ├───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libssh2-1             │ CVE-2026-7598  │          │              │ 1.10.0-3+b1               │               │ libssh2: integer overflow via large username or password     │
│                       │                │          │              │                           │               │ arguments                                                    │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-7598                    │
├───────────────────────┼────────────────┤          ├──────────────┼───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libtheora0            │ CVE-2026-5673  │          │ fix_deferred │ 1.1.1+dfsg.1-16.1+deb12u1 │               │ libtheora: libtheora: Denial of Service or Information       │
│                       │                │          │              │                           │               │ Disclosure via malformed AVI file...                         │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-5673                    │
├───────────────────────┼────────────────┤          ├──────────────┼───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libtiff6              │ CVE-2023-52355 │          │ will_not_fix │ 4.5.0-6+deb12u4           │               │ libtiff: TIFFRasterScanlineSize64 produce too-big size and   │
│                       │                │          │              │                           │               │ could cause OOM                                              │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2023-52355                   │
├───────────────────────┼────────────────┤          ├──────────────┼───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libtinfo6             │ CVE-2025-69720 │          │ affected     │ 6.4-4                     │               │ ncurses: ncurses: Buffer overflow vulnerability may lead to  │
│                       │                │          │              │                           │               │ arbitrary code execution.                                    │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2025-69720                   │
├───────────────────────┼────────────────┤          │              ├───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libwebsockets17       │ CVE-2025-11678 │          │              │ 4.1.6-3                   │               │ libwebsockets: Stack-based Buffer Overflow in libwebsockets  │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2025-11678                   │
├───────────────────────┼────────────────┤          │              ├───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libxml2               │ CVE-2026-6732  │          │              │ 2.9.14+dfsg-1.3~deb12u5   │               │ libxml2: libxml2: Denial of Service via crafted              │
│                       │                │          │              │                           │               │ XSD-validated document                                       │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-6732                    │
├───────────────────────┼────────────────┤          │              ├───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ mariadb-common        │ CVE-2025-13699 │          │              │ 1:10.11.14-0+deb12u2      │               │ mariadb: MariaDB: mariadb-dump utility vulnerable to remote  │
│                       │                │          │              │                           │               │ code execution via improper path...                          │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2025-13699                   │
├───────────────────────┼────────────────┤          │              ├───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ ncurses-base          │ CVE-2025-69720 │          │              │ 6.4-4                     │               │ ncurses: ncurses: Buffer overflow vulnerability may lead to  │
│                       │                │          │              │                           │               │ arbitrary code execution.                                    │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2025-69720                   │
├───────────────────────┤                │          │              │                           ├───────────────┤                                                              │
│ ncurses-bin           │                │          │              │                           │               │                                                              │
│                       │                │          │              │                           │               │                                                              │
│                       │                │          │              │                           │               │                                                              │
├───────────────────────┼────────────────┼──────────┤              ├───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ python3.11            │ CVE-2026-7210  │ CRITICAL │              │ 3.11.2-6+deb12u7          │               │ `xml.parsers.expat` and `xml.etree.ElementTree` use          │
│                       │                │          │              │                           │               │ insufficient entro ...                                       │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-7210                    │
│                       ├────────────────┼──────────┤              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2025-69534 │ HIGH     │              │                           │               │ python-markdown: denial of service via malformed HTML-like   │
│                       │                │          │              │                           │               │ sequences                                                    │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2025-69534                   │
│                       ├────────────────┤          │              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2026-6100  │          │              │                           │               │ python: Python: Arbitrary code execution or information      │
│                       │                │          │              │                           │               │ disclosure via use-after-free in decompression...            │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-6100                    │
├───────────────────────┼────────────────┼──────────┤              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│ python3.11-minimal    │ CVE-2026-7210  │ CRITICAL │              │                           │               │ `xml.parsers.expat` and `xml.etree.ElementTree` use          │
│                       │                │          │              │                           │               │ insufficient entro ...                                       │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-7210                    │
│                       ├────────────────┼──────────┤              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2025-69534 │ HIGH     │              │                           │               │ python-markdown: denial of service via malformed HTML-like   │
│                       │                │          │              │                           │               │ sequences                                                    │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2025-69534                   │
│                       ├────────────────┤          │              │                           ├───────────────┼──────────────────────────────────────────────────────────────┤
│                       │ CVE-2026-6100  │          │              │                           │               │ python: Python: Arbitrary code execution or information      │
│                       │                │          │              │                           │               │ disclosure via use-after-free in decompression...            │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2026-6100                    │
├───────────────────────┼────────────────┼──────────┼──────────────┼───────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ zlib1g                │ CVE-2023-45853 │ CRITICAL │ will_not_fix │ 1:1.2.13.dfsg-1           │               │ zlib: integer overflow and resultant heap-based buffer       │
│                       │                │          │              │                           │               │ overflow in zipOpenNewFileInZip4_6                           │
│                       │                │          │              │                           │               │ https://avd.aquasec.com/nvd/cve-2023-45853                   │
└───────────────────────┴────────────────┴──────────┴──────────────┴───────────────────────────┴───────────────┴──────────────────────────────────────────────────────────────┘
```

- tsisip/rtpengine:test: CRITICAL=10, HIGH=40

## Scanning: tsisip/ocp:test
```

Report Summary

┌────────────────────────────────┬────────┬─────────────────┬─────────┐
│             Target             │  Type  │ Vulnerabilities │ Secrets │
├────────────────────────────────┼────────┼─────────────────┼─────────┤
│ tsisip/ocp:test (debian 12.14) │ debian │       142       │    -    │
└────────────────────────────────┴────────┴─────────────────┴─────────┘
Legend:
- '-': Not scanned
- '0': Clean (no security findings detected)


tsisip/ocp:test (debian 12.14)
==============================
Total: 142 (HIGH: 140, CRITICAL: 2)

┌──────────────────┬────────────────┬──────────┬──────────────┬─────────────────────────┬───────────────┬──────────────────────────────────────────────────────────────┐
│     Library      │ Vulnerability  │ Severity │    Status    │    Installed Version    │ Fixed Version │                            Title                             │
├──────────────────┼────────────────┼──────────┼──────────────┼─────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ curl             │ CVE-2026-5773  │ HIGH     │ affected     │ 7.88.1-10+deb12u14      │               │ curl: libcurl: Wrong file transfer due to incorrect SMB      │
│                  │                │          │              │                         │               │ connection reuse                                             │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-5773                    │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-6276  │          │              │                         │               │ curl: libcurl: Information disclosure due to cookie leak     │
│                  │                │          │              │                         │               │ when reusing connections with...                             │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-6276                    │
├──────────────────┼────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│ libcurl4         │ CVE-2026-5773  │          │              │                         │               │ curl: libcurl: Wrong file transfer due to incorrect SMB      │
│                  │                │          │              │                         │               │ connection reuse                                             │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-5773                    │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-6276  │          │              │                         │               │ curl: libcurl: Information disclosure due to cookie leak     │
│                  │                │          │              │                         │               │ when reusing connections with...                             │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-6276                    │
├──────────────────┼────────────────┤          ├──────────────┼─────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libexpat1        │ CVE-2025-59375 │          │ will_not_fix │ 2.5.0-1+deb12u2         │               │ firefox: thunderbird: expat: libexpat in Expat allows        │
│                  │                │          │              │                         │               │ attackers to trigger large dynamic...                        │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-59375                   │
│                  ├────────────────┤          ├──────────────┤                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-25210 │          │ affected     │                         │               │ libexpat: libexpat: Information disclosure and data          │
│                  │                │          │              │                         │               │ integrity issues due to integer overflow...                  │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-25210                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-45186 │          │              │                         │               │ libexpat: denial of service via crafted XML input            │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-45186                   │
├──────────────────┼────────────────┤          │              ├─────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libgssapi-krb5-2 │ CVE-2026-40356 │          │              │ 1.20.1-2+deb12u5        │               │ krb5: MIT Kerberos 5 (krb5): Denial of Service via integer   │
│                  │                │          │              │                         │               │ underflow and...                                             │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-40356                   │
├──────────────────┤                │          │              │                         ├───────────────┤                                                              │
│ libk5crypto3     │                │          │              │                         │               │                                                              │
│                  │                │          │              │                         │               │                                                              │
│                  │                │          │              │                         │               │                                                              │
├──────────────────┤                │          │              │                         ├───────────────┤                                                              │
│ libkrb5-3        │                │          │              │                         │               │                                                              │
│                  │                │          │              │                         │               │                                                              │
│                  │                │          │              │                         │               │                                                              │
├──────────────────┤                │          │              │                         ├───────────────┤                                                              │
│ libkrb5support0  │                │          │              │                         │               │                                                              │
│                  │                │          │              │                         │               │                                                              │
│                  │                │          │              │                         │               │                                                              │
├──────────────────┼────────────────┤          │              ├─────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libldap-2.5-0    │ CVE-2023-2953  │          │              │ 2.5.13+dfsg-5           │               │ openldap: null pointer dereference in ber_memalloc_x         │
│                  │                │          │              │                         │               │ function                                                     │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2023-2953                    │
├──────────────────┼────────────────┤          │              ├─────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libncursesw6     │ CVE-2025-69720 │          │              │ 6.4-4                   │               │ ncurses: ncurses: Buffer overflow vulnerability may lead to  │
│                  │                │          │              │                         │               │ arbitrary code execution.                                    │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-69720                   │
├──────────────────┼────────────────┼──────────┤              ├─────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libsqlite3-0     │ CVE-2025-7458  │ CRITICAL │              │ 3.40.1-2+deb12u2        │               │ sqlite: SQLite integer overflow                              │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-7458                    │
├──────────────────┼────────────────┼──────────┤              ├─────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libssh2-1        │ CVE-2026-7598  │ HIGH     │              │ 1.10.0-3+b1             │               │ libssh2: integer overflow via large username or password     │
│                  │                │          │              │                         │               │ arguments                                                    │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-7598                    │
├──────────────────┼────────────────┤          │              ├─────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libtinfo6        │ CVE-2025-69720 │          │              │ 6.4-4                   │               │ ncurses: ncurses: Buffer overflow vulnerability may lead to  │
│                  │                │          │              │                         │               │ arbitrary code execution.                                    │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-69720                   │
├──────────────────┼────────────────┤          │              ├─────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ libxml2          │ CVE-2026-6732  │          │              │ 2.9.14+dfsg-1.3~deb12u5 │               │ libxml2: libxml2: Denial of Service via crafted              │
│                  │                │          │              │                         │               │ XSD-validated document                                       │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-6732                    │
├──────────────────┼────────────────┤          ├──────────────┼─────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ linux-libc-dev   │ CVE-2013-7445  │          │ will_not_fix │ 6.1.172-1               │               │ kernel: memory exhaustion via crafted Graphics Execution     │
│                  │                │          │              │                         │               │ Manager (GEM) objects                                        │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2013-7445                    │
│                  ├────────────────┤          ├──────────────┤                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2019-19449 │          │ fix_deferred │                         │               │ kernel: mounting a crafted f2fs filesystem image can lead to │
│                  │                │          │              │                         │               │ slab-out-of-bounds read...                                   │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2019-19449                   │
│                  ├────────────────┤          ├──────────────┤                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2019-19814 │          │ affected     │                         │               │ kernel: out-of-bounds write in __remove_dirty_segment in     │
│                  │                │          │              │                         │               │ fs/f2fs/segment.c                                            │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2019-19814                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2021-3847  │          │              │                         │               │ kernel: low-privileged user privileges escalation            │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2021-3847                    │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2021-3864  │          │              │                         │               │ kernel: descendant's dumpable setting with certain SUID      │
│                  │                │          │              │                         │               │ binaries                                                     │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2021-3864                    │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2023-52452 │          │              │                         │               │ kernel: bpf: Fix accesses to uninit stack slots              │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2023-52452                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2023-52586 │          │              │                         │               │ kernel: drm/msm/dpu: Add mutex lock in control vblank irq    │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2023-52586                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2023-52624 │          │              │                         │               │ kernel: drm/amd/display: Wake DMCUB before executing GPINT   │
│                  │                │          │              │                         │               │ commands                                                     │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2023-52624                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2023-52751 │          │              │                         │               │ kernel: smb: client: fix use-after-free in                   │
│                  │                │          │              │                         │               │ smb2_query_info_compound()                                   │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2023-52751                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2023-53218 │          │              │                         │               │ kernel: rxrpc: Make it so that a waiting process can be      │
│                  │                │          │              │                         │               │ aborted...                                                   │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2023-53218                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-21803 │          │              │                         │               │ kernel: bluetooth: use-after-free vulnerability in           │
│                  │                │          │              │                         │               │ af_bluetooth.c                                               │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-21803                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-26669 │          │              │                         │               │ kernel: net/sched: flower: Fix chain template offload        │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-26669                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-26836 │          │              │                         │               │ kernel: platform/x86: think-lmi: Fix password opcode         │
│                  │                │          │              │                         │               │ ordering for workstations                                    │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-26836                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-26913 │          │              │                         │               │ kernel: drm/amd/display: Fix dcn35 8k30 Underflow/Corruption │
│                  │                │          │              │                         │               │ Issue                                                        │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-26913                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-26914 │          │              │                         │               │ kernel: drm/amd/display: fix incorrect mpc_combine array     │
│                  │                │          │              │                         │               │ size                                                         │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-26914                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-26944 │          │              │                         │               │ kernel: btrfs: zoned: fix use-after-free in do_zone_finish() │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-26944                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-35887 │          │              │                         │               │ kernel: ax25: fix use-after-free bugs caused by              │
│                  │                │          │              │                         │               │ ax25_ds_del_timer                                            │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-35887                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-38570 │          │              │                         │               │ kernel: gfs2: Fix potential glock use-after-free on unmount  │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-38570                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-38630 │          │              │                         │               │ kernel: watchdog: cpu5wdt.c: Fix use-after-free bug caused   │
│                  │                │          │              │                         │               │ by cpu5wdt_trigger                                           │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-38630                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-41045 │          │              │                         │               │ kernel: bpf: Defer work in bpf_timer_cancel_and_free         │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-41045                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-41935 │          │              │                         │               │ kernel: f2fs: fix to shrink read extent node in batches      │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-41935                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-42118 │          │              │                         │               │ kernel: drm/amd/display: Do not return negative stream id    │
│                  │                │          │              │                         │               │ for array                                                    │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-42118                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-42162 │          │              │                         │               │ kernel: gve: Account for stopped queues when reading NIC     │
│                  │                │          │              │                         │               │ stats                                                        │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-42162                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-44941 │          │              │                         │               │ kernel: f2fs: fix to cover read extent cache access with     │
│                  │                │          │              │                         │               │ lock                                                         │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-44941                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-44942 │          │              │                         │               │ kernel: f2fs: fix to do sanity check on F2FS_INLINE_DATA     │
│                  │                │          │              │                         │               │ flag in inode...                                             │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-44942                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-44951 │          │              │                         │               │ kernel: serial: sc16is7xx: fix TX fifo corruption            │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-44951                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-46729 │          │              │                         │               │ kernel: drm/amd/display: Fix incorrect size calculation for  │
│                  │                │          │              │                         │               │ loop                                                         │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-46729                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-46811 │          │              │                         │               │ kernel: drm/amd/display: Fix index may exceed array range    │
│                  │                │          │              │                         │               │ within fpu_update_bw_bounding_box                            │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-46811                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-46813 │          │              │                         │               │ kernel: drm/amd/display: Check link_index before accessing   │
│                  │                │          │              │                         │               │ dc-&gt;links[]                                               │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-46813                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-47691 │          │              │                         │               │ kernel: f2fs: fix to avoid use-after-free in                 │
│                  │                │          │              │                         │               │ f2fs_stop_gc_thread()                                        │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-47691                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-50029 │          │              │                         │               │ kernel: Bluetooth: hci_conn: Fix UAF in                      │
│                  │                │          │              │                         │               │ hci_enhanced_setup_sync                                      │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-50029                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-50217 │          │              │                         │               │ kernel: btrfs: fix use-after-free of block device file in    │
│                  │                │          │              │                         │               │ __btrfs_free_extra_devids()                                  │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-50217                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-50226 │          │              │                         │               │ kernel: cxl/port: Fix use-after-free, permit out-of-order    │
│                  │                │          │              │                         │               │ decoder shutdown                                             │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-50226                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-50282 │          │              │                         │               │ kernel: drm/amdgpu: add missing size check in                │
│                  │                │          │              │                         │               │ amdgpu_debugfs_gprwave_read()                                │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-50282                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-53068 │          │              │                         │               │ kernel: firmware: arm_scmi: Fix slab-use-after-free in       │
│                  │                │          │              │                         │               │ scmi_bus_notifier()                                          │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-53068                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-53147 │          │              │                         │               │ kernel: exfat: fix out-of-bounds access of directory entries │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-53147                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-53168 │          │              │                         │               │ kernel: sunrpc: fix one UAF issue caused by sunrpc kernel    │
│                  │                │          │              │                         │               │ tcp socket...                                                │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-53168                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-53179 │          │              │                         │               │ kernel: smb: client: fix use-after-free of signing key       │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-53179                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-53218 │          │              │                         │               │ kernel: f2fs: fix race in concurrent f2fs_stop_gc_thread     │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-53218                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-56538 │          │              │                         │               │ kernel: drm: zynqmp_kms: Unplug DRM device before removal    │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-56538                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-56775 │          │              │                         │               │ kernel: drm/amd/display: Fix handling of plane refcount      │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-56775                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-57900 │          │              │                         │               │ kernel: ila: serialize calls to nf_register_net_hooks()      │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-57900                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-57982 │          │              │                         │               │ kernel: xfrm: state: fix out-of-bounds read during lookup    │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-57982                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2024-58093 │          │              │                         │               │ kernel: Linux kernel: PCI/ASPM use-after-free during         │
│                  │                │          │              │                         │               │ hot-unplug                                                   │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2024-58093                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-21863 │          │              │                         │               │ kernel: io_uring: prevent opcode speculation                 │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-21863                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-21927 │          │              │                         │               │ kernel: nvme-tcp: fix potential memory corruption in         │
│                  │                │          │              │                         │               │ nvme_tcp_recv_pdu()                                          │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-21927                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-21967 │          │              │                         │               │ kernel: ksmbd: fix use-after-free in ksmbd_free_work_struct  │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-21967                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-21969 │          │              │                         │               │ kernel: Bluetooth: L2CAP: Fix slab-use-after-free Read in    │
│                  │                │          │              │                         │               │ l2cap_send_cmd                                               │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-21969                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-21985 │          │              │                         │               │ kernel: drm/amd/display: Fix out-of-bound accesses           │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-21985                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-22039 │          │              │                         │               │ kernel: ksmbd: fix overflow in dacloffset bounds check       │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-22039                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-22104 │          │              │                         │               │ kernel: ibmvnic: Use kernel helpers for hex dumps            │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-22104                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-23133 │          │              │                         │               │ kernel: wifi: ath11k: update channel list in reg notifier    │
│                  │                │          │              │                         │               │ instead reg worker...                                        │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-23133                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-37750 │          │              │                         │               │ kernel: smb: client: fix UAF in decryption with multichannel │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-37750                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-37776 │          │              │                         │               │ kernel: ksmbd: fix use-after-free in                         │
│                  │                │          │              │                         │               │ smb_break_all_levII_oplock()                                 │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-37776                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-37777 │          │              │                         │               │ kernel: ksmbd: fix use-after-free in                         │
│                  │                │          │              │                         │               │ __smb2_lease_break_noti()                                    │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-37777                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-37861 │          │              │                         │               │ kernel: scsi: mpi3mr: Synchronous access b/w reset and tm    │
│                  │                │          │              │                         │               │ thread for reply...                                          │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-37861                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-37952 │          │              │                         │               │ kernel: ksmbd: Fix UAF in __close_file_table_ids             │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-37952                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-37957 │          │              │                         │               │ kernel: KVM: SVM: Forcibly leave SMM mode on SHUTDOWN        │
│                  │                │          │              │                         │               │ interception                                                 │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-37957                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-38069 │          │              │                         │               │ kernel: PCI: endpoint: pci-epf-test: Fix double free that    │
│                  │                │          │              │                         │               │ causes kernel to oops...                                     │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-38069                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-38081 │          │              │                         │               │ kernel: spi-rockchip: Fix register out of bounds access      │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-38081                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-38117 │          │              │                         │               │ kernel: Bluetooth: MGMT: Protect mgmt_pending list with its  │
│                  │                │          │              │                         │               │ own lock                                                     │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-38117                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-38182 │          │              │                         │               │ kernel: ublk: santizize the arguments from userspace when    │
│                  │                │          │              │                         │               │ adding a device                                              │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-38182                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-38204 │          │              │                         │               │ kernel: jfs: fix array-index-out-of-bounds read in           │
│                  │                │          │              │                         │               │ add_missing_indices                                          │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-38204                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-38206 │          │              │                         │               │ kernel: Kernel: Double free vulnerability in exFAT           │
│                  │                │          │              │                         │               │ filesystem can lead to denial...                             │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-38206                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-38248 │          │              │                         │               │ kernel: Linux kernel:A use-after-free in bridge multicast in │
│                  │                │          │              │                         │               │ br_multicast_port_ctx_init                                   │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-38248                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-38369 │          │              │                         │               │ kernel: dmaengine: idxd: Check availability of workqueue     │
│                  │                │          │              │                         │               │ allocated by idxd wq driver...                               │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-38369                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-38582 │          │              │                         │               │ kernel: RDMA/hns: Fix double destruction of rsv_qp           │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-38582                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-38584 │          │              │                         │               │ kernel: padata: Fix pd UAF once and for all                  │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-38584                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-38585 │          │              │                         │               │ kernel: staging: media: atomisp: Fix stack buffer overflow   │
│                  │                │          │              │                         │               │ in gmin_get_var_int()                                        │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-38585                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-38595 │          │              │                         │               │ kernel: xen: fix UAF in dmabuf_exp_from_pages()              │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-38595                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-38616 │          │              │                         │               │ kernel: Linux kernel: Denial of Service in kTLS due to race  │
│                  │                │          │              │                         │               │ condition...                                                 │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-38616                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-38627 │          │              │                         │               │ kernel: f2fs: compress: fix UAF of f2fs_inode_info in        │
│                  │                │          │              │                         │               │ f2fs_free_dic                                                │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-38627                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-38636 │          │              │                         │               │ kernel: rv: Use strings in da monitors tracepoints           │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-38636                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-38722 │          │              │                         │               │ kernel: habanalabs: fix UAF in export_dmabuf()               │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-38722                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-38734 │          │              │                         │               │ kernel: net/smc: fix UAF on smcsk after smc_listen_out()     │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-38734                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-39744 │          │              │                         │               │ kernel: rcu: Fix rcu_read_unlock() deadloop due to IRQ work  │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-39744                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-39797 │          │              │                         │               │ kernel: xfrm: Duplicate SPI Handling                         │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-39797                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-39810 │          │              │                         │               │ kernel: bnxt_en: Fix memory corruption when FW resources     │
│                  │                │          │              │                         │               │ change during ifdown                                         │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-39810                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-39859 │          │              │                         │               │ kernel: ptp: ocp: fix use-after-free bugs causing by         │
│                  │                │          │              │                         │               │ ptp_ocp_watchdog                                             │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-39859                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-39901 │          │              │                         │               │ kernel: i40e: remove read access to debugfs files            │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-39901                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-39952 │          │              │                         │               │ kernel: wifi: wilc1000: avoid buffer overflow in WID string  │
│                  │                │          │              │                         │               │ configuration                                                │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-39952                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-40347 │          │              │                         │               │ kernel: net: enetc: fix the deadlock of enetc_mdio_lock      │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-40347                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-71068 │          │              │                         │               │ kernel: svcrdma: bound check rq_pages index in inline path   │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-71068                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-71073 │          │              │                         │               │ kernel: Input: lkkbd - disable pending work before freeing   │
│                  │                │          │              │                         │               │ device                                                       │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-71073                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2025-71152 │          │              │                         │               │ kernel: net: dsa: properly keep track of conduit reference   │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-71152                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-23171 │          │              │                         │               │ kernel: Linux kernel: Use-after-free in bonding module can   │
│                  │                │          │              │                         │               │ cause system crash or...                                     │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-23171                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-23191 │          │              │                         │               │ kernel: ALSA: aloop: Fix racy access at PCM trigger          │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-23191                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-23208 │          │              │                         │               │ kernel: ALSA: usb-audio: Prevent excessive number of frames  │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-23208                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-23226 │          │              │                         │               │ kernel: ksmbd: add chann_lock to protect ksmbd_chann_list    │
│                  │                │          │              │                         │               │ xarray                                                       │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-23226                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-23327 │          │              │                         │               │ kernel: cxl/mbox: validate payload size before accessing     │
│                  │                │          │              │                         │               │ contents in cxl_payload_from_user_allowed()                  │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-23327                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-23361 │          │              │                         │               │ kernel: PCI: dwc: ep: Flush MSI-X write before unmapping its │
│                  │                │          │              │                         │               │ ATU entry...                                                 │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-23361                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-23447 │          │              │                         │               │ kernel: net: usb: cdc_ncm: add ndpoffset to NDP32 nframes    │
│                  │                │          │              │                         │               │ bounds check                                                 │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-23447                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-23448 │          │              │                         │               │ kernel: net: usb: cdc_ncm: add ndpoffset to NDP16 nframes    │
│                  │                │          │              │                         │               │ bounds check                                                 │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-23448                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31419 │          │              │                         │               │ kernel: Linux kernel: Use-after-free in bonding driver leads │
│                  │                │          │              │                         │               │ to denial of service...                                      │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31419                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31489 │          │              │                         │               │ kernel: spi: meson-spicc: Fix double-put in remove path      │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31489                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31493 │          │              │                         │               │ kernel: RDMA/efa: Fix use of completion ctx after free       │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31493                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31500 │          │              │                         │               │ kernel: Bluetooth: btintel: serialize btintel_hw_error()     │
│                  │                │          │              │                         │               │ with hci_req_sync_lock                                       │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31500                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31527 │          │              │                         │               │ kernel: driver core: platform: use generic driver_override   │
│                  │                │          │              │                         │               │ infrastructure                                               │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31527                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31532 │          │              │                         │               │ kernel: can: raw: fix ro->uniq use-after-free in raw_rcv()   │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31532                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31568 │          │              │                         │               │ kernel: s390/mm: Add missing secure storage access fixups    │
│                  │                │          │              │                         │               │ for donated memory                                           │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31568                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31576 │          │              │                         │               │ kernel: media: hackrf: fix to not free memory after the      │
│                  │                │          │              │                         │               │ device is...                                                 │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31576                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31578 │          │              │                         │               │ kernel: media: as102: fix to not free memory after the       │
│                  │                │          │              │                         │               │ device is...                                                 │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31578                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31580 │          │              │                         │               │ kernel: bcache: fix cached_dev.sb_bio use-after-free and     │
│                  │                │          │              │                         │               │ crash                                                        │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31580                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31581 │          │              │                         │               │ kernel: ALSA: 6fire: fix use-after-free on disconnect        │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31581                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31583 │          │              │                         │               │ kernel: media: em28xx: fix use-after-free in                 │
│                  │                │          │              │                         │               │ em28xx_v4l2_open()                                           │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31583                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31607 │          │              │                         │               │ kernel: usbip: validate number_of_packets in                 │
│                  │                │          │              │                         │               │ usbip_pack_ret_submit()                                      │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31607                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31663 │          │              │                         │               │ kernel: xfrm: hold dev ref until after transport_finish      │
│                  │                │          │              │                         │               │ NF_HOOK                                                      │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31663                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31686 │          │              │                         │               │ kernel: mm/kasan: fix double free for kasan pXds             │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31686                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31688 │          │              │                         │               │ kernel: driver core: enforce device_lock for                 │
│                  │                │          │              │                         │               │ driver_match_device()                                        │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31688                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31696 │          │              │                         │               │ kernel: rxrpc: Fix missing validation of ticket length in    │
│                  │                │          │              │                         │               │ non-XDR key preparsing...                                    │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31696                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31702 │          │              │                         │               │ kernel: f2fs: fix use-after-free of sbi in                   │
│                  │                │          │              │                         │               │ f2fs_compress_write_end_io()                                 │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31702                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31709 │          │              │                         │               │ kernel: smb: client: validate the whole DACL before          │
│                  │                │          │              │                         │               │ rewriting it in cifsacl...                                   │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31709                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31715 │          │              │                         │               │ kernel: f2fs: fix UAF caused by decrementing sbi->nr_pages[] │
│                  │                │          │              │                         │               │ in f2fs_write_end_io()                                       │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31715                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-31729 │          │              │                         │               │ kernel: usb: typec: ucsi: validate connector number in       │
│                  │                │          │              │                         │               │ ucsi_notify_common()                                         │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-31729                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-43049 │          │              │                         │               │ kernel: HID: logitech-hidpp: Prevent use-after-free on force │
│                  │                │          │              │                         │               │ feedback initialisation failure                              │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-43049                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-43052 │          │              │                         │               │ kernel: wifi: mac80211: check tdls flag in                   │
│                  │                │          │              │                         │               │ ieee80211_tdls_oper                                          │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-43052                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-43125 │          │              │                         │               │ kernel: dlm: validate length in dlm_search_rsb_tree          │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-43125                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-43198 │          │              │                         │               │ kernel: tcp: fix potential race in tcp_v6_syn_recv_sock()    │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-43198                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-43250 │          │              │                         │               │ kernel: usb: chipidea: udc: fix DMA and SG cleanup in        │
│                  │                │          │              │                         │               │ _ep_nuke()                                                   │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-43250                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-43494 │          │              │                         │               │ kernel: net/rds: reset op_nents when zerocopy page pin fails │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-43494                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-43501 │          │              │                         │               │ kernel: ipv6: rpl: reserve mac_len headroom when             │
│                  │                │          │              │                         │               │ recompressed SRH grows                                       │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-43501                   │
│                  ├────────────────┤          │              │                         ├───────────────┼──────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-46300 │          │              │                         │               │ kernel: "Fragnesia" is a variant of Dirty Frag vulnerability │
│                  │                │          │              │                         │               │ in the ESP/XFRM...                                           │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2026-46300                   │
├──────────────────┼────────────────┤          │              ├─────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ ncurses-base     │ CVE-2025-69720 │          │              │ 6.4-4                   │               │ ncurses: ncurses: Buffer overflow vulnerability may lead to  │
│                  │                │          │              │                         │               │ arbitrary code execution.                                    │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2025-69720                   │
├──────────────────┤                │          │              │                         ├───────────────┤                                                              │
│ ncurses-bin      │                │          │              │                         │               │                                                              │
│                  │                │          │              │                         │               │                                                              │
│                  │                │          │              │                         │               │                                                              │
├──────────────────┼────────────────┼──────────┼──────────────┼─────────────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ zlib1g           │ CVE-2023-45853 │ CRITICAL │ will_not_fix │ 1:1.2.13.dfsg-1         │               │ zlib: integer overflow and resultant heap-based buffer       │
│                  │                │          │              │                         │               │ overflow in zipOpenNewFileInZip4_6                           │
│                  │                │          │              │                         │               │ https://avd.aquasec.com/nvd/cve-2023-45853                   │
└──────────────────┴────────────────┴──────────┴──────────────┴─────────────────────────┴───────────────┴──────────────────────────────────────────────────────────────┘
```

- tsisip/ocp:test: CRITICAL=2, HIGH=140

## Scanning: tsisip/asterisk:test
```
```

- tsisip/asterisk:test: CRITICAL=0, HIGH=0

## Scanning: tsisip/backup:test
```

Report Summary

┌──────────────────────────────────┬──────────┬─────────────────┬─────────┐
│              Target              │   Type   │ Vulnerabilities │ Secrets │
├──────────────────────────────────┼──────────┼─────────────────┼─────────┤
│ tsisip/backup:test (debian 13.5) │  debian  │       18        │    -    │
├──────────────────────────────────┼──────────┼─────────────────┼─────────┤
│ usr/local/bin/gosu               │ gobinary │       12        │    -    │
└──────────────────────────────────┴──────────┴─────────────────┴─────────┘
Legend:
- '-': Not scanned
- '0': Clean (no security findings detected)


tsisip/backup:test (debian 13.5)
================================
Total: 18 (HIGH: 16, CRITICAL: 2)

┌──────────────────┬────────────────┬──────────┬──────────┬──────────────────────────────────────┬───────────────┬─────────────────────────────────────────────────────────────┐
│     Library      │ Vulnerability  │ Severity │  Status  │          Installed Version           │ Fixed Version │                            Title                            │
├──────────────────┼────────────────┼──────────┼──────────┼──────────────────────────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ dirmngr          │ CVE-2026-24882 │ HIGH     │ affected │ 2.4.7-21+deb13u1+b3                  │               │ GnuPG: GnuPG: Stack-based buffer overflow in tpm2daemon     │
│                  │                │          │          │                                      │               │ allows arbitrary code execution                             │
│                  │                │          │          │                                      │               │ https://avd.aquasec.com/nvd/cve-2026-24882                  │
├──────────────────┤                │          │          ├──────────────────────────────────────┼───────────────┤                                                             │
│ gnupg            │                │          │          │ 2.4.7-21+deb13u1                     │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┤                │          │          │                                      ├───────────────┤                                                             │
│ gnupg-l10n       │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┤                │          │          ├──────────────────────────────────────┼───────────────┤                                                             │
│ gpg              │                │          │          │ 2.4.7-21+deb13u1+b3                  │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┤                │          │          │                                      ├───────────────┤                                                             │
│ gpg-agent        │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┤                │          │          │                                      ├───────────────┤                                                             │
│ gpgconf          │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┤                │          │          │                                      ├───────────────┤                                                             │
│ gpgsm            │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┼────────────────┤          │          ├──────────────────────────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ libgssapi-krb5-2 │ CVE-2026-40356 │          │          │ 1.21.3-5+deb13u1                     │               │ krb5: MIT Kerberos 5 (krb5): Denial of Service via integer  │
│                  │                │          │          │                                      │               │ underflow and...                                            │
│                  │                │          │          │                                      │               │ https://avd.aquasec.com/nvd/cve-2026-40356                  │
├──────────────────┤                │          │          │                                      ├───────────────┤                                                             │
│ libk5crypto3     │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┤                │          │          │                                      ├───────────────┤                                                             │
│ libkrb5-3        │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┤                │          │          │                                      ├───────────────┤                                                             │
│ libkrb5support0  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┼────────────────┤          │          ├──────────────────────────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ libncursesw6     │ CVE-2025-69720 │          │          │ 6.5+20250216-2                       │               │ ncurses: ncurses: Buffer overflow vulnerability may lead to │
│                  │                │          │          │                                      │               │ arbitrary code execution.                                   │
│                  │                │          │          │                                      │               │ https://avd.aquasec.com/nvd/cve-2025-69720                  │
├──────────────────┤                │          │          │                                      ├───────────────┤                                                             │
│ libtinfo6        │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┼────────────────┤          │          ├──────────────────────────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ libxml2          │ CVE-2026-6732  │          │          │ 2.12.7+dfsg+really2.9.14-2.1+deb13u2 │               │ libxml2: libxml2: Denial of Service via crafted             │
│                  │                │          │          │                                      │               │ XSD-validated document                                      │
│                  │                │          │          │                                      │               │ https://avd.aquasec.com/nvd/cve-2026-6732                   │
├──────────────────┼────────────────┤          │          ├──────────────────────────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ ncurses-base     │ CVE-2025-69720 │          │          │ 6.5+20250216-2                       │               │ ncurses: ncurses: Buffer overflow vulnerability may lead to │
│                  │                │          │          │                                      │               │ arbitrary code execution.                                   │
│                  │                │          │          │                                      │               │ https://avd.aquasec.com/nvd/cve-2025-69720                  │
├──────────────────┤                │          │          │                                      ├───────────────┤                                                             │
│ ncurses-bin      │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
│                  │                │          │          │                                      │               │                                                             │
├──────────────────┼────────────────┼──────────┤          ├──────────────────────────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ rclone           │ CVE-2026-41176 │ CRITICAL │          │ 1.60.1+dfsg-4                        │               │ github.com/rclone/rclone: Rclone: Unauthorized access to    │
│                  │                │          │          │                                      │               │ administrative functions through unauthenticated Remote     │
│                  │                │          │          │                                      │               │ Control endpoint....                                        │
│                  │                │          │          │                                      │               │ https://avd.aquasec.com/nvd/cve-2026-41176                  │
│                  ├────────────────┤          │          │                                      ├───────────────┼─────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-41179 │          │          │                                      │               │ github.com/rclone/rclone: Rclone: Unauthenticated local     │
│                  │                │          │          │                                      │               │ command execution via exposed RC endpoint                   │
│                  │                │          │          │                                      │               │ https://avd.aquasec.com/nvd/cve-2026-41179                  │
└──────────────────┴────────────────┴──────────┴──────────┴──────────────────────────────────────┴───────────────┴─────────────────────────────────────────────────────────────┘

usr/local/bin/gosu (gobinary)
=============================
Total: 12 (HIGH: 11, CRITICAL: 1)

┌─────────┬────────────────┬──────────┬────────┬───────────────────┬──────────────────────────────┬──────────────────────────────────────────────────────────────┐
│ Library │ Vulnerability  │ Severity │ Status │ Installed Version │        Fixed Version         │                            Title                             │
├─────────┼────────────────┼──────────┼────────┼───────────────────┼──────────────────────────────┼──────────────────────────────────────────────────────────────┤
│ stdlib  │ CVE-2025-68121 │ CRITICAL │ fixed  │ v1.24.6           │ 1.24.13, 1.25.7, 1.26.0-rc.3 │ crypto/tls: crypto/tls: Incorrect certificate validation     │
│         │                │          │        │                   │                              │ during TLS session resumption                                │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2025-68121                   │
│         ├────────────────┼──────────┤        │                   ├──────────────────────────────┼──────────────────────────────────────────────────────────────┤
│         │ CVE-2025-61726 │ HIGH     │        │                   │ 1.24.12, 1.25.6              │ golang: net/url: Memory exhaustion in query parameter        │
│         │                │          │        │                   │                              │ parsing in net/url                                           │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2025-61726                   │
│         ├────────────────┤          │        │                   ├──────────────────────────────┼──────────────────────────────────────────────────────────────┤
│         │ CVE-2025-61729 │          │        │                   │ 1.24.11, 1.25.5              │ crypto/x509: golang: Denial of Service due to excessive      │
│         │                │          │        │                   │                              │ resource consumption via crafted...                          │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2025-61729                   │
│         ├────────────────┤          │        │                   ├──────────────────────────────┼──────────────────────────────────────────────────────────────┤
│         │ CVE-2026-25679 │          │        │                   │ 1.25.8, 1.26.1               │ net/url: Incorrect parsing of IPv6 host literals in net/url  │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2026-25679                   │
│         ├────────────────┤          │        │                   ├──────────────────────────────┼──────────────────────────────────────────────────────────────┤
│         │ CVE-2026-32280 │          │        │                   │ 1.25.9, 1.26.2               │ crypto/x509: crypto/tls: golang: Go: Denial of Service       │
│         │                │          │        │                   │                              │ vulnerability in certificate chain building...               │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2026-32280                   │
│         ├────────────────┤          │        │                   │                              ├──────────────────────────────────────────────────────────────┤
│         │ CVE-2026-32281 │          │        │                   │                              │ crypto/x509: golang: Go crypto/x509: Denial of Service via   │
│         │                │          │        │                   │                              │ inefficient certificate chain validation...                  │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2026-32281                   │
│         ├────────────────┤          │        │                   │                              ├──────────────────────────────────────────────────────────────┤
│         │ CVE-2026-32283 │          │        │                   │                              │ crypto/tls: golang: Go crypto/tls: Denial of Service via     │
│         │                │          │        │                   │                              │ multiple TLS 1.3 key...                                      │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2026-32283                   │
│         ├────────────────┤          │        │                   ├──────────────────────────────┼──────────────────────────────────────────────────────────────┤
│         │ CVE-2026-33811 │          │        │                   │ 1.25.10, 1.26.3              │ When using LookupCNAME with the cgo DNS resolver, a very     │
│         │                │          │        │                   │                              │ long CNAME...                                                │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2026-33811                   │
│         ├────────────────┤          │        │                   │                              ├──────────────────────────────────────────────────────────────┤
│         │ CVE-2026-33814 │          │        │                   │                              │ When processing HTTP/2 SETTINGS frames, transport will enter │
│         │                │          │        │                   │                              │ an infini ...                                                │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2026-33814                   │
│         ├────────────────┤          │        │                   │                              ├──────────────────────────────────────────────────────────────┤
│         │ CVE-2026-39820 │          │        │                   │                              │ Well-crafted inputs reaching ParseAddress, ParseAddressList, │
│         │                │          │        │                   │                              │ and Parse ...                                                │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2026-39820                   │
│         ├────────────────┤          │        │                   │                              ├──────────────────────────────────────────────────────────────┤
│         │ CVE-2026-39836 │          │        │                   │                              │ Panic in Dial and LookupPort when handling NUL byte on       │
│         │                │          │        │                   │                              │ Windows in...                                                │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2026-39836                   │
│         ├────────────────┤          │        │                   │                              ├──────────────────────────────────────────────────────────────┤
│         │ CVE-2026-42499 │          │        │                   │                              │ Pathological inputs could cause DoS through consumePhrase    │
│         │                │          │        │                   │                              │ when parsing ...                                             │
│         │                │          │        │                   │                              │ https://avd.aquasec.com/nvd/cve-2026-42499                   │
└─────────┴────────────────┴──────────┴────────┴───────────────────┴──────────────────────────────┴──────────────────────────────────────────────────────────────┘
```

- tsisip/backup:test: CRITICAL=3, HIGH=27

## Scanning: tsisip/certbot:test
```

Report Summary

┌──────────────────────────────────────────────────────────────────────────────────┬────────────┬─────────────────┬─────────┐
│                                      Target                                      │    Type    │ Vulnerabilities │ Secrets │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ tsisip/certbot:test (alpine 3.23.4)                                              │   alpine   │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ opt/certbot/src/acme/src/acme.egg-info/PKG-INFO                                  │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ opt/certbot/src/certbot/src/certbot.egg-info/PKG-INFO                            │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/acme-5.6.0.dist-info/METADATA             │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/certbot-5.6.0.dist-info/METADATA          │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/certifi-2026.4.22.dist-info/METADATA      │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/cffi-2.0.0.dist-info/METADATA             │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/charset_normalizer-3.4.7.dist-info/METAD- │ python-pkg │        0        │    -    │
│ ATA                                                                              │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/configargparse-1.7.5.dist-info/METADATA   │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/configobj-5.0.9.dist-info/METADATA        │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/cryptography-47.0.0.dist-info/METADATA    │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/distro-1.9.0.dist-info/METADATA           │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/idna-3.13.dist-info/METADATA              │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/josepy-2.2.0.dist-info/METADATA           │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/packaging-26.2.dist-info/METADATA         │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/parsedatetime-2.6.dist-info/METADATA      │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/pip-26.1.dist-info/METADATA               │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/pycparser-3.0.dist-info/METADATA          │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/pyopenssl-26.1.0.dist-info/METADATA       │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/pyrfc3339-2.1.0.dist-info/METADATA        │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/requests-2.33.1.dist-info/METADATA        │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/setuptools-82.0.1.dist-info/METADATA      │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/setuptools/_vendor/autocommand-2.2.2.dis- │ python-pkg │        0        │    -    │
│ t-info/METADATA                                                                  │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/setuptools/_vendor/backports.tarfile-1.2- │ python-pkg │        0        │    -    │
│ .0.dist-info/METADATA                                                            │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/setuptools/_vendor/importlib_metadata-8.- │ python-pkg │        0        │    -    │
│ 7.1.dist-info/METADATA                                                           │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/setuptools/_vendor/jaraco.text-4.0.0.dis- │ python-pkg │        0        │    -    │
│ t-info/METADATA                                                                  │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/setuptools/_vendor/jaraco_context-6.1.0.- │ python-pkg │        0        │    -    │
│ dist-info/METADATA                                                               │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/setuptools/_vendor/jaraco_functools-4.4.- │ python-pkg │        0        │    -    │
│ 0.dist-info/METADATA                                                             │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/setuptools/_vendor/more_itertools-10.8.0- │ python-pkg │        0        │    -    │
│ .dist-info/METADATA                                                              │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/setuptools/_vendor/packaging-26.0.dist-i- │ python-pkg │        0        │    -    │
│ nfo/METADATA                                                                     │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/setuptools/_vendor/platformdirs-4.4.0.di- │ python-pkg │        0        │    -    │
│ st-info/METADATA                                                                 │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/setuptools/_vendor/tomli-2.4.0.dist-info- │ python-pkg │        0        │    -    │
│ /METADATA                                                                        │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/setuptools/_vendor/wheel-0.46.3.dist-inf- │ python-pkg │        0        │    -    │
│ o/METADATA                                                                       │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/setuptools/_vendor/zipp-3.23.0.dist-info- │ python-pkg │        0        │    -    │
│ /METADATA                                                                        │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/urllib3-2.6.3.dist-info/METADATA          │ python-pkg │        2        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/uv-0.11.8.dist-info/METADATA              │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.14/site-packages/wheel-0.47.0.dist-info/METADATA           │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/bin/uv                                                                 │ rustbinary │        1        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/bin/uvx                                                                │ rustbinary │        1        │    -    │
└──────────────────────────────────────────────────────────────────────────────────┴────────────┴─────────────────┴─────────┘
Legend:
- '-': Not scanned
- '0': Clean (no security findings detected)


Python (python-pkg)
===================
Total: 2 (HIGH: 2, CRITICAL: 0)

┌────────────────────┬────────────────┬──────────┬────────┬───────────────────┬───────────────┬────────────────────────────────────────────────────────────┐
│      Library       │ Vulnerability  │ Severity │ Status │ Installed Version │ Fixed Version │                           Title                            │
├────────────────────┼────────────────┼──────────┼────────┼───────────────────┼───────────────┼────────────────────────────────────────────────────────────┤
│ urllib3 (METADATA) │ CVE-2026-44431 │ HIGH     │ fixed  │ 2.6.3             │ 2.7.0         │ urllib3 is an HTTP client library for Python. From 1.23 to │
│                    │                │          │        │                   │               │ before...                                                  │
│                    │                │          │        │                   │               │ https://avd.aquasec.com/nvd/cve-2026-44431                 │
│                    ├────────────────┤          │        │                   │               ├────────────────────────────────────────────────────────────┤
│                    │ CVE-2026-44432 │          │        │                   │               │ urllib3: urllib3: Denial of Service due to excessive HTTP  │
│                    │                │          │        │                   │               │ response decompression                                     │
│                    │                │          │        │                   │               │ https://avd.aquasec.com/nvd/cve-2026-44432                 │
└────────────────────┴────────────────┴──────────┴────────┴───────────────────┴───────────────┴────────────────────────────────────────────────────────────┘

usr/local/bin/uv (rustbinary)
=============================
Total: 1 (HIGH: 1, CRITICAL: 0)

┌───────────────┬─────────────────────┬──────────┬────────┬───────────────────┬───────────────────────────┬─────────────────────────────────────────────────────────────┐
│    Library    │    Vulnerability    │ Severity │ Status │ Installed Version │       Fixed Version       │                            Title                            │
├───────────────┼─────────────────────┼──────────┼────────┼───────────────────┼───────────────────────────┼─────────────────────────────────────────────────────────────┤
│ rustls-webpki │ GHSA-82j2-j2ch-gfr8 │ HIGH     │ fixed  │ 0.103.12          │ 0.103.13, 0.104.0-alpha.7 │ rustls-webpki: Denial of service via panic on malformed CRL │
│               │                     │          │        │                   │                           │ BIT STRING                                                  │
│               │                     │          │        │                   │                           │ https://github.com/advisories/GHSA-82j2-j2ch-gfr8           │
└───────────────┴─────────────────────┴──────────┴────────┴───────────────────┴───────────────────────────┴─────────────────────────────────────────────────────────────┘

usr/local/bin/uvx (rustbinary)
==============================
Total: 1 (HIGH: 1, CRITICAL: 0)

┌───────────────┬─────────────────────┬──────────┬────────┬───────────────────┬───────────────────────────┬─────────────────────────────────────────────────────────────┐
│    Library    │    Vulnerability    │ Severity │ Status │ Installed Version │       Fixed Version       │                            Title                            │
├───────────────┼─────────────────────┼──────────┼────────┼───────────────────┼───────────────────────────┼─────────────────────────────────────────────────────────────┤
│ rustls-webpki │ GHSA-82j2-j2ch-gfr8 │ HIGH     │ fixed  │ 0.103.12          │ 0.103.13, 0.104.0-alpha.7 │ rustls-webpki: Denial of service via panic on malformed CRL │
│               │                     │          │        │                   │                           │ BIT STRING                                                  │
│               │                     │          │        │                   │                           │ https://github.com/advisories/GHSA-82j2-j2ch-gfr8           │
└───────────────┴─────────────────────┴──────────┴────────┴───────────────────┴───────────────────────────┴─────────────────────────────────────────────────────────────┘
```

- tsisip/certbot:test: CRITICAL=0, HIGH=4

## Scanning: tsisip/certbot-exporter:test
```

Report Summary

┌──────────────────────────────────────────────────────────────────────────────────┬────────────┬─────────────────┬─────────┐
│                                      Target                                      │    Type    │ Vulnerabilities │ Secrets │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ tsisip/certbot-exporter:test (debian 13.5)                                       │   debian   │       13        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/pip-24.0.dist-info/METADATA               │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/prometheus_client-0.20.0.dist-info/METAD- │ python-pkg │        0        │    -    │
│ ATA                                                                              │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/setuptools-79.0.1.dist-info/METADATA      │ python-pkg │        0        │    -    │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/setuptools/_vendor/autocommand-2.2.2.dis- │ python-pkg │        0        │    -    │
│ t-info/METADATA                                                                  │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/setuptools/_vendor/backports.tarfile-1.2- │ python-pkg │        0        │    -    │
│ .0.dist-info/METADATA                                                            │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/setuptools/_vendor/importlib_metadata-8.- │ python-pkg │        0        │    -    │
│ 0.0.dist-info/METADATA                                                           │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/setuptools/_vendor/inflect-7.3.1.dist-in- │ python-pkg │        0        │    -    │
│ fo/METADATA                                                                      │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/setuptools/_vendor/jaraco.collections-5.- │ python-pkg │        0        │    -    │
│ 1.0.dist-info/METADATA                                                           │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/setuptools/_vendor/jaraco.context-5.3.0.- │ python-pkg │        1        │    -    │
│ dist-info/METADATA                                                               │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/setuptools/_vendor/jaraco.functools-4.0.- │ python-pkg │        0        │    -    │
│ 1.dist-info/METADATA                                                             │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/setuptools/_vendor/jaraco.text-3.12.1.di- │ python-pkg │        0        │    -    │
│ st-info/METADATA                                                                 │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/setuptools/_vendor/more_itertools-10.3.0- │ python-pkg │        0        │    -    │
│ .dist-info/METADATA                                                              │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/setuptools/_vendor/packaging-24.2.dist-i- │ python-pkg │        0        │    -    │
│ nfo/METADATA                                                                     │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/setuptools/_vendor/platformdirs-4.2.2.di- │ python-pkg │        0        │    -    │
│ st-info/METADATA                                                                 │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/setuptools/_vendor/tomli-2.0.1.dist-info- │ python-pkg │        0        │    -    │
│ /METADATA                                                                        │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/setuptools/_vendor/typeguard-4.3.0.dist-- │ python-pkg │        0        │    -    │
│ info/METADATA                                                                    │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/setuptools/_vendor/typing_extensions-4.1- │ python-pkg │        0        │    -    │
│ 2.2.dist-info/METADATA                                                           │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/setuptools/_vendor/wheel-0.45.1.dist-inf- │ python-pkg │        1        │    -    │
│ o/METADATA                                                                       │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/setuptools/_vendor/zipp-3.19.2.dist-info- │ python-pkg │        0        │    -    │
│ /METADATA                                                                        │            │                 │         │
├──────────────────────────────────────────────────────────────────────────────────┼────────────┼─────────────────┼─────────┤
│ usr/local/lib/python3.11/site-packages/wheel-0.45.1.dist-info/METADATA           │ python-pkg │        1        │    -    │
└──────────────────────────────────────────────────────────────────────────────────┴────────────┴─────────────────┴─────────┘
Legend:
- '-': Not scanned
- '0': Clean (no security findings detected)


tsisip/certbot-exporter:test (debian 13.5)
==========================================
Total: 13 (HIGH: 13, CRITICAL: 0)

┌──────────────────┬────────────────┬──────────┬──────────┬───────────────────┬───────────────┬─────────────────────────────────────────────────────────────┐
│     Library      │ Vulnerability  │ Severity │  Status  │ Installed Version │ Fixed Version │                            Title                            │
├──────────────────┼────────────────┼──────────┼──────────┼───────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ curl             │ CVE-2026-5773  │ HIGH     │ affected │ 8.14.1-2+deb13u3  │               │ curl: libcurl: Wrong file transfer due to incorrect SMB     │
│                  │                │          │          │                   │               │ connection reuse                                            │
│                  │                │          │          │                   │               │ https://avd.aquasec.com/nvd/cve-2026-5773                   │
│                  ├────────────────┤          │          │                   ├───────────────┼─────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-6276  │          │          │                   │               │ curl: libcurl: Information disclosure due to cookie leak    │
│                  │                │          │          │                   │               │ when reusing connections with...                            │
│                  │                │          │          │                   │               │ https://avd.aquasec.com/nvd/cve-2026-6276                   │
├──────────────────┼────────────────┤          │          │                   ├───────────────┼─────────────────────────────────────────────────────────────┤
│ libcurl4t64      │ CVE-2026-5773  │          │          │                   │               │ curl: libcurl: Wrong file transfer due to incorrect SMB     │
│                  │                │          │          │                   │               │ connection reuse                                            │
│                  │                │          │          │                   │               │ https://avd.aquasec.com/nvd/cve-2026-5773                   │
│                  ├────────────────┤          │          │                   ├───────────────┼─────────────────────────────────────────────────────────────┤
│                  │ CVE-2026-6276  │          │          │                   │               │ curl: libcurl: Information disclosure due to cookie leak    │
│                  │                │          │          │                   │               │ when reusing connections with...                            │
│                  │                │          │          │                   │               │ https://avd.aquasec.com/nvd/cve-2026-6276                   │
├──────────────────┼────────────────┤          │          ├───────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ libgssapi-krb5-2 │ CVE-2026-40356 │          │          │ 1.21.3-5+deb13u1  │               │ krb5: MIT Kerberos 5 (krb5): Denial of Service via integer  │
│                  │                │          │          │                   │               │ underflow and...                                            │
│                  │                │          │          │                   │               │ https://avd.aquasec.com/nvd/cve-2026-40356                  │
├──────────────────┤                │          │          │                   ├───────────────┤                                                             │
│ libk5crypto3     │                │          │          │                   │               │                                                             │
│                  │                │          │          │                   │               │                                                             │
│                  │                │          │          │                   │               │                                                             │
├──────────────────┤                │          │          │                   ├───────────────┤                                                             │
│ libkrb5-3        │                │          │          │                   │               │                                                             │
│                  │                │          │          │                   │               │                                                             │
│                  │                │          │          │                   │               │                                                             │
├──────────────────┤                │          │          │                   ├───────────────┤                                                             │
│ libkrb5support0  │                │          │          │                   │               │                                                             │
│                  │                │          │          │                   │               │                                                             │
│                  │                │          │          │                   │               │                                                             │
├──────────────────┼────────────────┤          │          ├───────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ libncursesw6     │ CVE-2025-69720 │          │          │ 6.5+20250216-2    │               │ ncurses: ncurses: Buffer overflow vulnerability may lead to │
│                  │                │          │          │                   │               │ arbitrary code execution.                                   │
│                  │                │          │          │                   │               │ https://avd.aquasec.com/nvd/cve-2025-69720                  │
├──────────────────┼────────────────┤          │          ├───────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ libssh2-1t64     │ CVE-2026-7598  │          │          │ 1.11.1-1          │               │ libssh2: integer overflow via large username or password    │
│                  │                │          │          │                   │               │ arguments                                                   │
│                  │                │          │          │                   │               │ https://avd.aquasec.com/nvd/cve-2026-7598                   │
├──────────────────┼────────────────┤          │          ├───────────────────┼───────────────┼─────────────────────────────────────────────────────────────┤
│ libtinfo6        │ CVE-2025-69720 │          │          │ 6.5+20250216-2    │               │ ncurses: ncurses: Buffer overflow vulnerability may lead to │
│                  │                │          │          │                   │               │ arbitrary code execution.                                   │
│                  │                │          │          │                   │               │ https://avd.aquasec.com/nvd/cve-2025-69720                  │
├──────────────────┤                │          │          │                   ├───────────────┤                                                             │
│ ncurses-base     │                │          │          │                   │               │                                                             │
│                  │                │          │          │                   │               │                                                             │
│                  │                │          │          │                   │               │                                                             │
├──────────────────┤                │          │          │                   ├───────────────┤                                                             │
│ ncurses-bin      │                │          │          │                   │               │                                                             │
│                  │                │          │          │                   │               │                                                             │
│                  │                │          │          │                   │               │                                                             │
└──────────────────┴────────────────┴──────────┴──────────┴───────────────────┴───────────────┴─────────────────────────────────────────────────────────────┘

Python (python-pkg)
===================
Total: 3 (HIGH: 3, CRITICAL: 0)

┌───────────────────────────┬────────────────┬──────────┬────────┬───────────────────┬───────────────┬──────────────────────────────────────────────────────────────┐
│          Library          │ Vulnerability  │ Severity │ Status │ Installed Version │ Fixed Version │                            Title                             │
├───────────────────────────┼────────────────┼──────────┼────────┼───────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ jaraco.context (METADATA) │ CVE-2026-23949 │ HIGH     │ fixed  │ 5.3.0             │ 6.1.0         │ jaraco.context: jaraco.context: Path traversal via malicious │
│                           │                │          │        │                   │               │ tar archives                                                 │
│                           │                │          │        │                   │               │ https://avd.aquasec.com/nvd/cve-2026-23949                   │
├───────────────────────────┼────────────────┤          │        ├───────────────────┼───────────────┼──────────────────────────────────────────────────────────────┤
│ wheel (METADATA)          │ CVE-2026-24049 │          │        │ 0.45.1            │ 0.46.2        │ wheel: wheel: Privilege Escalation or Arbitrary Code         │
│                           │                │          │        │                   │               │ Execution via malicious wheel file...                        │
│                           │                │          │        │                   │               │ https://avd.aquasec.com/nvd/cve-2026-24049                   │
│                           │                │          │        │                   │               │                                                              │
│                           │                │          │        │                   │               │                                                              │
│                           │                │          │        │                   │               │                                                              │
│                           │                │          │        │                   │               │                                                              │
└───────────────────────────┴────────────────┴──────────┴────────┴───────────────────┴───────────────┴──────────────────────────────────────────────────────────────┘
```

- tsisip/certbot-exporter:test: CRITICAL=0, HIGH=16

---
**Summary**: Total CRITICAL=17, HIGH=263
**Status**: REVIEW — 17 CRITICAL, 263 HIGH vulnerabilities remain
