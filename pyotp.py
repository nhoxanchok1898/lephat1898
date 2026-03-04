"""
Minimal pyotp-compatible module for environments without external dependency.
Implements the subset used by this project/tests:
- random_base32()
- TOTP(secret).now()
- TOTP(secret).verify(token, valid_window=...)
- TOTP(secret).provisioning_uri(name=..., issuer_name=...)
"""
from __future__ import annotations

import base64
import hashlib
import hmac
import secrets
import time
import urllib.parse


_BASE32_ALPHABET = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567"


def random_base32(length: int = 32) -> str:
    if length <= 0:
        length = 32
    return "".join(secrets.choice(_BASE32_ALPHABET) for _ in range(length))


class TOTP:
    def __init__(self, secret: str, digits: int = 6, interval: int = 30):
        self.secret = (secret or "").strip().replace(" ", "")
        self.digits = int(digits)
        self.interval = int(interval)

    def _secret_bytes(self) -> bytes:
        normalized = self.secret.upper()
        # Pad base32 if needed.
        missing = len(normalized) % 8
        if missing:
            normalized += "=" * (8 - missing)
        return base64.b32decode(normalized, casefold=True)

    def _timecode(self, for_time: int | float | None = None) -> int:
        ts = int(time.time() if for_time is None else for_time)
        return int(ts // self.interval)

    def _generate(self, counter: int) -> str:
        key = self._secret_bytes()
        msg = counter.to_bytes(8, "big")
        digest = hmac.new(key, msg, hashlib.sha1).digest()
        offset = digest[-1] & 0x0F
        code_int = int.from_bytes(digest[offset : offset + 4], "big") & 0x7FFFFFFF
        code_int %= 10 ** self.digits
        return str(code_int).zfill(self.digits)

    def at(self, for_time: int | float) -> str:
        return self._generate(self._timecode(for_time))

    def now(self) -> str:
        return self._generate(self._timecode())

    def verify(self, otp: str, for_time: int | float | None = None, valid_window: int = 0) -> bool:
        token = (otp or "").strip()
        if not token.isdigit():
            return False

        center = self._timecode(for_time)
        window = int(valid_window or 0)
        for delta in range(-window, window + 1):
            if hmac.compare_digest(self._generate(center + delta), token):
                return True
        return False

    def provisioning_uri(self, name: str, issuer_name: str | None = None) -> str:
        issuer = (issuer_name or "").strip()
        account = (name or "").strip()
        if issuer:
            label = f"{issuer}:{account}"
        else:
            label = account

        params = {"secret": self.secret}
        if issuer:
            params["issuer"] = issuer
        query = urllib.parse.urlencode(params)
        return f"otpauth://totp/{urllib.parse.quote(label)}?{query}"

