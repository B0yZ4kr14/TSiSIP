"""Minimal RFC 6238 TOTP implementation for integration tests."""
import base64
import hmac
import hashlib
import struct
import time


def base32Decode(s):
    m = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567"
    s = s.upper().replace("=", "")
    buf = 0
    bits = 0
    out = []
    for ch in s:
        val = m.find(ch)
        if val < 0:
            continue
        buf = (buf << 5) | val
        bits += 5
        if bits >= 8:
            bits -= 8
            out.append((buf >> bits) & 0xFF)
    return bytes(out)


def generateTotpCode(secret, timeStep=30, t=None):
    t = t or int(time.time())
    counter = int(t // timeStep)
    secretBin = base32Decode(secret)
    counterBin = struct.pack(">Q", counter)
    h = hmac.new(secretBin, counterBin, hashlib.sha1).digest()
    offset = h[19] & 0x0F
    code = ((h[offset] & 0x7F) << 24 | (h[offset + 1] & 0xFF) << 16 | (h[offset + 2] & 0xFF) << 8 | (h[offset + 3] & 0xFF)) % 1000000
    return f"{code:06d}"


def verifyTotpCode(secret, code, window=1):
    t = int(time.time())
    for i in range(-window, window + 1):
        if generateTotpCode(secret, 30, t + i * 30) == code:
            return True
    return False
