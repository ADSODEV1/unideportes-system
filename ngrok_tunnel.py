#!/usr/bin/env python3
"""
Levanta un tunel ngrok para el proyecto Unideportes y muestra URLs directas.

Requisitos:
- pip install pyngrok
- (Opcional) Definir NGROK_AUTHTOKEN en variables de entorno.
"""

import argparse
import os
import signal
import sys
import time

from pyngrok import conf, ngrok


def build_urls(public_base_url: str) -> dict:
    base = public_base_url.rstrip("/")
    return {
        "home": f"{base}/public/index.php",
        "admin": f"{base}/views/panel_admin.php",
    }


def main() -> int:
    parser = argparse.ArgumentParser(description="Inicia tunel ngrok para Unideportes")
    parser.add_argument("--port", type=int, default=80, help="Puerto local de Apache/XAMPP")
    parser.add_argument("--region", default="us", help="Region de ngrok (us, sa, eu, etc.)")
    args = parser.parse_args()

    authtoken = os.getenv("NGROK_AUTHTOKEN", "").strip()
    if authtoken:
        conf.get_default().auth_token = authtoken

    tunnel = ngrok.connect(addr=args.port, bind_tls=True, region=args.region)
    public_url = tunnel.public_url
    urls = build_urls(public_url)

    print("\nTunel ngrok activo")
    print("-" * 60)
    print(f"Base URL:   {public_url}")
    print(f"Index:      {urls['home']}")
    print(f"Admin panel:{urls['admin']}")
    print("-" * 60)
    print("Nota: El panel admin requiere autenticacion.")
    print("Presiona Ctrl+C para cerrar el tunel.\n")

    def _stop(_sig, _frame):
        ngrok.kill()
        sys.exit(0)

    signal.signal(signal.SIGINT, _stop)
    signal.signal(signal.SIGTERM, _stop)

    while True:
        time.sleep(1)


if __name__ == "__main__":
    raise SystemExit(main())
