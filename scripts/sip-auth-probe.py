#!/usr/bin/env python3
"""Minimal SIP digest probe for TSiSIP production validation."""

from __future__ import annotations

import argparse
import hashlib
import re
import socket
import uuid


def build_invite(
    uri: str,
    source: str,
    port: int,
    user: str,
    domain: str,
    call_id: str,
    cseq: int,
    tag: str,
    auth: str | None = None,
) -> bytes:
    branch = "z9hG4bK" + uuid.uuid4().hex[:10]
    headers = (
        f"INVITE {uri} SIP/2.0\r\n"
        f"Via: SIP/2.0/UDP {source}:{port};branch={branch}\r\n"
        "Max-Forwards: 70\r\n"
        f"From: <sip:{user}@{domain}>;tag={tag}\r\n"
        f"To: <{uri}>\r\n"
        f"Call-ID: {call_id}\r\n"
        f"CSeq: {cseq} INVITE\r\n"
        f"Contact: <sip:{user}@{source}:{port}>\r\n"
    )
    if auth:
        headers += auth + "\r\n"
    return (headers + "Content-Length: 0\r\n\r\n").encode()


def parse_www_authenticate(response: str) -> dict[str, str]:
    match = re.search(r"WWW-Authenticate:\s*Digest\s+([^\r\n]+)", response, re.I)
    if not match:
        raise RuntimeError(f"missing WWW-Authenticate header in {response.splitlines()[0]}")
    values: dict[str, str] = {}
    for key, quoted, bare in re.findall(r'(\w+)=(?:"([^"]*)"|([^,\s]+))', match.group(1)):
        values[key] = quoted or bare
    return values


def digest_authorization(
    values: dict[str, str],
    method: str,
    uri: str,
    user: str,
    password: str,
) -> str:
    realm = values.get("realm", "")
    nonce = values["nonce"]
    qop = values.get("qop", "auth").strip('"') or "auth"
    algorithm = values.get("algorithm", "MD5")
    nc = "00000001"
    cnonce = uuid.uuid4().hex[:16]
    ha1 = hashlib.md5(f"{user}:{realm}:{password}".encode()).hexdigest()
    ha2 = hashlib.md5(f"{method}:{uri}".encode()).hexdigest()
    response = hashlib.md5(f"{ha1}:{nonce}:{nc}:{cnonce}:{qop}:{ha2}".encode()).hexdigest()
    return (
        'Authorization: Digest '
        f'username="{user}", realm="{realm}", nonce="{nonce}", uri="{uri}", '
        f'response="{response}", algorithm={algorithm}, qop={qop}, nc={nc}, cnonce="{cnonce}"'
    )


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--target", required=True)
    parser.add_argument("--source", required=True)
    parser.add_argument("--port", type=int, default=5096)
    parser.add_argument("--user", default="devuser")
    parser.add_argument("--domain", default="dev.tsisip.local")
    parser.add_argument("--password", default="devpass")
    parser.add_argument("--uri", default="sip:1000@dev.tsisip.local")
    args = parser.parse_args()

    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    sock.bind((args.source, args.port))
    sock.settimeout(8)

    call_id = str(uuid.uuid4())
    tag = uuid.uuid4().hex[:8]

    sock.sendto(
        build_invite(args.uri, args.source, args.port, args.user, args.domain, call_id, 1, tag),
        (args.target, 5060),
    )
    first = sock.recvfrom(8192)[0].decode(errors="replace")
    print("challenge", first.split("\r\n", 1)[0])

    auth = digest_authorization(parse_www_authenticate(first), "INVITE", args.uri, args.user, args.password)
    sock.sendto(
        build_invite(args.uri, args.source, args.port, args.user, args.domain, call_id, 2, tag, auth),
        (args.target, 5060),
    )

    for _ in range(5):
        try:
            reply = sock.recvfrom(8192)[0].decode(errors="replace")
        except socket.timeout:
            print("auth-reply timeout")
            return 1
        status = reply.split("\r\n", 1)[0]
        print("auth-reply", status)
        if not status.startswith("SIP/2.0 100"):
            return 0
    return 1


if __name__ == "__main__":
    raise SystemExit(main())
