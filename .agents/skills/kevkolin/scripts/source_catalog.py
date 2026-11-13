#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Kevkolin Source Catalog & Research Dossier Formatter
Manages the authoritative registry of verified local (Diyarbakır/Amed), national, regional, and international media sources
and formats structured Research Dossiers for downstream processing by Kevrast and Kevanger.
"""

import sys
import os
import json
import argparse
import re
import email.utils
import unicodedata
import xml.etree.ElementTree as ET
from datetime import datetime, timezone, timedelta
from typing import Dict, List, Any, Optional
from pathlib import Path

# Resolve shared directory
_SHARED_DIR = Path(__file__).resolve().parents[2] / "_shared"
if str(_SHARED_DIR) not in sys.path:
    sys.path.insert(0, str(_SHARED_DIR))

try:
    import editorial_http
except ImportError:
    editorial_http = None

try:
    from zoneinfo import ZoneInfo
    ISTANBUL = ZoneInfo("Europe/Istanbul")
except Exception:
    ISTANBUL = timezone(timedelta(hours=3))

VERIFIED_SOURCES = {
    "amed_diyarbakir": {
        "quota": 19,
        "sources": [
            {"name": "Amida Haber", "url": "https://amidahaber.com", "type": "local_media", "feed": "https://amidahaber.com/rss", "feed_type": "rss"},
            {"name": "Tigris Haber", "url": "https://www.tigrishaber.com", "type": "local_media", "feed": "https://www.tigrishaber.com/rss", "feed_type": "rss"},
            {"name": "Güneydoğu Ekspres", "url": "https://www.guneydoguekspres.com", "type": "local_media", "feed": "https://www.guneydoguekspres.com/rss", "feed_type": "rss"},
            {"name": "Diyarbakır Yenigün", "url": "https://diyarbakiryenigun.com", "type": "local_media", "feed": "https://diyarbakiryenigun.com/rss", "feed_type": "rss"},
            {"name": "Mücadele Gazetesi", "url": "https://www.mucadelegazetesi.com.tr", "type": "local_media", "feed": "https://www.mucadelegazetesi.com.tr/rss", "feed_type": "rss"},
            {"name": "CEGA Medya", "url": "https://www.cegamedya.com", "type": "local_media", "feed": "https://www.cegamedya.com/rss", "feed_type": "rss"},
            {"name": "Amed Times", "url": "https://www.amedtimes.com.tr", "type": "local_media"},
            {"name": "Diyarbakır Söz", "url": "https://diyarbakirsoz.com", "type": "local_media", "feed": "https://diyarbakirsoz.com/rss", "feed_type": "rss"},
            {"name": "Özgür Haber Gazetesi", "url": "https://www.ozgurhabergazetesi.com", "type": "local_media"},
            {"name": "Güneydoğu Güncel", "url": "https://www.guneydoguguncel.com", "type": "local_media", "feed": "https://www.guneydoguguncel.com/rss.xml", "feed_type": "rss"},
            {"name": "Amed Haber", "url": "https://www.amedhaber.net", "type": "local_media", "feed": "https://www.amedhaber.net/rss", "feed_type": "rss"},
            {"name": "Amed Radyo TV", "url": "http://www.amedradyotv.com", "type": "local_media"},
            {"name": "Diyarbakır Büyükşehir Belediyesi", "url": "https://www.diyarbakir.bel.tr", "type": "institutional", "feed": "https://diyarbakir.bel.tr/tr/sitemap.xml", "feed_type": "sitemap"},
            {"name": "Diyarbakır Ticaret ve Sanayi Odası (DTSO)", "url": "https://www.dtso.org.tr", "type": "institutional"},
            {"name": "Diyarbakır Ticaret Borsası (DTB)", "url": "https://www.diyarbakirtb.org.tr", "type": "institutional"},
            {"name": "Diyarbakır Barosu", "url": "https://diyarbakirbarosu.org.tr", "type": "institutional"},
            {"name": "Karacadağ Kalkınma Ajansı", "url": "https://www.karacadag.gov.tr", "type": "institutional"},
            {"name": "Dicle Üniversitesi", "url": "https://www.dicle.edu.tr", "type": "institutional"},
            {"name": "Amedspor (Resmi)", "url": "https://www.amedspor.com.tr", "type": "sports"}
        ]
    },
    "regional_and_middle_east": [
        {"name": "Rûdaw Türkçe", "urls": ["https://rudaw.net/turkish", "https://rudaw.net/english"], "feed": "https://rudaw.net/sitemap.xml", "feed_type": "sitemap"},
        {"name": "Kurdistan24 Türkçe", "urls": ["https://www.kurdistan24.net/tr", "https://www.kurdistan24.net/en"]},
        {"name": "964media", "urls": ["https://964media.com"]},
        {"name": "Shafaq News", "urls": ["https://shafaq.com"]},
        {"name": "Bianet", "urls": ["https://bianet.org"], "feed": "https://bianet.org/sitemap/haberler", "feed_type": "sitemap"},
        {"name": "Mezopotamya Ajansı (MA)", "urls": ["https://mezopotamyaajansi45.com"]},
        {"name": "Official KRG Portals", "urls": ["https://gov.krd", "https://presidency.gov.krd"]}
    ],
    "united_states": {
        "quota": 10,
        "sources": [
            {"name": "The New York Times", "url": "https://www.nytimes.com"},
            {"name": "The Washington Post", "url": "https://www.washingtonpost.com"},
            {"name": "The Wall Street Journal", "url": "https://www.wsj.com"},
            {"name": "Associated Press (AP)", "url": "https://apnews.com"},
            {"name": "Bloomberg News", "url": "https://www.bloomberg.com"},
            {"name": "Foreign Policy", "url": "https://foreignpolicy.com"},
            {"name": "Foreign Affairs", "url": "https://www.foreignaffairs.com"},
            {"name": "Al-Monitor", "url": "https://www.al-monitor.com"},
            {"name": "Politico US", "url": "https://www.politico.com"},
            {"name": "CNN International", "url": "https://edition.cnn.com"}
        ]
    },
    "continental_europe": {
        "quota": 11,
        "sources": [
            {"name": "Deutsche Welle (DW)", "country": "Germany", "url": "https://www.dw.com"},
            {"name": "Der Spiegel", "country": "Germany", "url": "https://www.spiegel.de"},
            {"name": "Agence France-Presse (AFP)", "country": "France", "url": "https://www.afp.com"},
            {"name": "Le Monde", "country": "France", "url": "https://www.lemonde.fr"},
            {"name": "France 24", "country": "France", "url": "https://www.france24.com"},
            {"name": "Euronews", "country": "EU", "url": "https://www.euronews.com"},
            {"name": "Politico Europe", "country": "EU", "url": "https://www.politico.eu"},
            {"name": "SWI swissinfo.ch", "country": "Switzerland", "url": "https://www.swissinfo.ch"},
            {"name": "SVT Nyheter", "country": "Sweden", "url": "https://www.svt.se"},
            {"name": "El País", "country": "Spain", "url": "https://elpais.com"},
            {"name": "NOS", "country": "Netherlands", "url": "https://nos.nl"}
        ]
    },
    "united_kingdom": {
        "quota": 3,
        "sources": [
            {"name": "BBC News", "url": "https://www.bbc.com"},
            {"name": "Reuters", "url": "https://www.reuters.com"},
            {"name": "The Guardian", "url": "https://www.theguardian.com"}
        ]
    },
    "israel": {
        "quota": 5,
        "sources": [
            {"name": "The Jerusalem Post", "url": "https://www.jpost.com"},
            {"name": "Haaretz", "url": "https://www.haaretz.com"},
            {"name": "The Times of Israel", "url": "https://www.timesofisrael.com"},
            {"name": "Ynetnews", "url": "https://www.ynetnews.com"},
            {"name": "i24NEWS", "url": "https://www.i24news.tv"}
        ]
    },
    "russia": {
        "quota": 3,
        "sources": [
            {"name": "TASS", "url": "https://tass.com"},
            {"name": "RIA Novosti", "url": "https://ria.ru"},
            {"name": "Kommersant", "url": "https://www.kommersant.ru"}
        ]
    },
    "ukraine": {
        "quota": 3,
        "sources": [
            {"name": "Ukrinform", "url": "https://www.ukrinform.net"},
            {"name": "The Kyiv Independent", "url": "https://kyivindependent.com"},
            {"name": "Ukrainska Pravda", "url": "https://www.pravda.com.ua/eng/"}
        ]
    },
    "turkey": {
        "quota": 3,
        "sources": [
            {"name": "Anadolu Ajansı (AA)", "url": "https://www.aa.com.tr", "feed": "https://www.aa.com.tr/tr/rss/default?cat=guncel", "feed_type": "rss"},
            {"name": "T24", "url": "https://t24.com.tr"},
            {"name": "Gazete Duvar", "url": "https://www.gazeteduvar.com.tr", "feed": "https://www.gazeteduvar.com.tr/rss", "feed_type": "rss"}
        ]
    },
    "iran": {
        "quota": 1,
        "sources": [
            {"name": "IRNA", "url": "https://en.irna.ir"}
        ]
    }
}

def tr_fold(text: str) -> str:
    """Case-fold handling Turkish dotted/dotless I."""
    if not text:
        return ""
    res = text.replace("İ", "i").replace("I", "ı")
    res = unicodedata.normalize("NFC", res).lower()
    return res.replace("\u0307", "")

def parse_entry_datetime(val: str) -> Optional[datetime]:
    """Parse feed date string (RFC 2822 or ISO 8601) and normalize to Europe/Istanbul."""
    if not val:
        return None
    val = val.strip()
    # 1. RFC 2822
    try:
        dt = email.utils.parsedate_to_datetime(val)
        if dt.tzinfo is None:
            dt = dt.replace(tzinfo=ISTANBUL)
        return dt.astimezone(ISTANBUL)
    except Exception:
        pass
    # 2. ISO 8601
    try:
        iso_val = val.replace("Z", "+00:00")
        dt = datetime.fromisoformat(iso_val)
        if dt.tzinfo is None:
            dt = dt.replace(tzinfo=ISTANBUL)
        return dt.astimezone(ISTANBUL)
    except Exception:
        pass
    return None

def scan_single_feed(source_name: str, feed_url: str, target_date: datetime.date, query: str = "") -> List[Dict[str, Any]]:
    """Fetch and parse feed items published on target_date, optionally filtered by query."""
    if not editorial_http:
        return []
    status, body, _ = editorial_http.fetch_html(
        feed_url,
        timeout=10,
        max_bytes=10_000_000,
        use_cache=True,
        headers={"Accept": "application/rss+xml, application/atom+xml, application/xml, text/xml, */*;q=0.8"}
    )
    if status != 200 or not body:
        return []

    cleaned = body.lstrip("\ufeff").strip()
    try:
        root = ET.fromstring(cleaned)
    except ET.ParseError:
        try:
            sanitized = re.sub(r"&(?!(amp|lt|gt|quot|apos|#\d+|#x[0-9a-fA-F]+);)", "&amp;", cleaned)
            root = ET.fromstring(sanitized)
        except Exception:
            return []

    items = []
    q_folded = tr_fold(query) if query else ""

    # RSS items
    for node in root.findall(".//item"):
        title = (node.findtext("title") or "").strip()
        link = (node.findtext("link") or "").strip()
        pub_raw = (node.findtext("pubDate") or "").strip()
        desc = (node.findtext("description") or "").strip()

        dt = parse_entry_datetime(pub_raw)
        if not dt or dt.date() != target_date:
            continue
        if q_folded and q_folded not in tr_fold(f"{title} {desc} {link}"):
            continue
        items.append({
            "outlet": source_name,
            "title": title,
            "url": link,
            "pub_date": pub_raw,
            "pub_date_iso": dt.isoformat(),
            "role": "primary"
        })

    # Atom entries
    for node in root.findall(".//{*}entry"):
        title = (node.findtext("{*}title") or "").strip()
        link_elem = node.find("{*}link")
        link = (link_elem.get("href") or link_elem.text or "").strip() if link_elem is not None else ""
        pub_raw = (node.findtext("{*}published") or node.findtext("{*}updated") or "").strip()
        summary = (node.findtext("{*}summary") or node.findtext("{*}content") or "").strip()

        dt = parse_entry_datetime(pub_raw)
        if not dt or dt.date() != target_date:
            continue
        if q_folded and q_folded not in tr_fold(f"{title} {summary} {link}"):
            continue
        items.append({
            "outlet": source_name,
            "title": title,
            "url": link,
            "pub_date": pub_raw,
            "pub_date_iso": dt.isoformat(),
            "role": "primary"
        })

    # Sitemap URLs
    for node in root.findall(".//{*}url"):
        loc = (node.findtext("{*}loc") or "").strip()
        lastmod = (node.findtext("{*}lastmod") or "").strip()
        news_node = node.find("{*}news")

        title = ""
        pub_raw = lastmod
        if news_node is not None:
            t = news_node.findtext("{*}title")
            d = news_node.findtext("{*}publication_date")
            if t: title = t.strip()
            if d: pub_raw = d.strip()
        elif lastmod:
            title = loc.rstrip("/").split("/")[-1].replace("-", " ").title()

        dt = parse_entry_datetime(pub_raw)
        if not dt or dt.date() != target_date:
            continue
        if q_folded and q_folded not in tr_fold(f"{title} {loc}"):
            continue
        items.append({
            "outlet": source_name,
            "title": title,
            "url": loc,
            "pub_date": pub_raw,
            "pub_date_iso": dt.isoformat(),
            "role": "primary"
        })

    return items

def create_dossier_template(topic: str, date: str) -> Dict[str, Any]:
    return {
        "topic": topic,
        "date": date,
        "type": "news",
        "summary_of_event": "",
        "key_entities": [],
        "timeline": [],
        "verified_facts": {
            "numbers_and_metrics": [],
            "dates_and_times": [],
            "official_statements_and_quotes": []
        },
        "sources": [
            {
                "url": "https://example.com/REPLACE_WITH_PRIMARY_URL",
                "role": "primary",
                "outlet": "",
                "pub_date": date or "",
                "claims": []
            }
        ],
        "contradictions": [],
        "status": "ready_for_kevrast_stage_1"
    }

def main():
    parser = argparse.ArgumentParser(description="Kevkolin Source Catalog & Dossier Formatter")
    parser.add_argument("--list-sources", action="store_true", help="List all verified sources and quotas")
    parser.add_argument("--category", default="", help="Filter listed sources by category key (e.g. amed_diyarbakir)")
    parser.add_argument("--json", action="store_true", help="Output source list or scan results in JSON")
    parser.add_argument("--new-dossier", help="Generate a blank structured research dossier for a topic to stdout")
    parser.add_argument("--init-dossier", help="Initialize a structured research dossier at specified path")
    parser.add_argument("--topic", default="", help="Topic title for the dossier")
    parser.add_argument("--date", default="", help="Date string for the dossier or scan (YYYY-MM-DD, defaults to today in Europe/Istanbul)")
    parser.add_argument("--scan-today", action="store_true", help="Scan verified feeds for articles published on target date")
    parser.add_argument("--query", "-q", default="", help="Keyword or entity filter for scanned articles")
    parser.add_argument("--seed-url", default="", help="Overnight seed URL to be demoted to role: supplementary")
    parser.add_argument("--force", action="store_true", help="Force overwrite existing dossier file")

    args = parser.parse_args()

    if args.list_sources:
        selected = VERIFIED_SOURCES
        if args.category:
            key = args.category.strip().lower()
            selected = {k: v for k, v in VERIFIED_SOURCES.items() if k == key}
        if args.json:
            print(json.dumps(selected, ensure_ascii=False, indent=2))
        else:
            print("=" * 60)
            print("KEVKOLIN: Authoritative Verified Source Registry")
            print("=" * 60)
            for region, data in selected.items():
                print(f"\n[{region.upper().replace('_', ' ')}]")
                if isinstance(data, list):
                    for s in data:
                        urls = ", ".join(s.get("urls", []))
                        print(f"  - {s['name']}: {urls}")
                elif isinstance(data, dict):
                    print(f"  Quota: {data.get('quota', 'N/A')}")
                    for s in data.get("sources", []):
                        extra = f" [{s.get('country')}]" if "country" in s else (f" [{s.get('type')}]" if "type" in s else "")
                        urls_str = ", ".join(s["urls"]) if "urls" in s else s.get("url", "")
                        print(f"  - {s['name']}{extra}: {urls_str}")
            print("=" * 60)

    elif args.scan_today:
        if args.date:
            try:
                target_date = datetime.strptime(args.date.strip(), "%Y-%m-%d").date()
            except ValueError:
                print(f"Error: Invalid date format '{args.date}'. Expected YYYY-MM-DD.", file=sys.stderr)
                sys.exit(1)
        else:
            target_date = datetime.now(ISTANBUL).date()

        cat_key = args.category.strip().lower() if args.category else "amed_diyarbakir"
        if cat_key not in VERIFIED_SOURCES:
            print(f"Error: Unknown source category '{cat_key}'.", file=sys.stderr)
            sys.exit(1)

        sources_data = VERIFIED_SOURCES[cat_key]
        if isinstance(sources_data, dict):
            source_list = sources_data.get("sources", [])
        elif isinstance(sources_data, list):
            source_list = sources_data
        else:
            source_list = []

        all_items: List[Dict[str, Any]] = []
        seen_urls = set()

        for s in source_list:
            feed_url = s.get("feed")
            if feed_url:
                feed_items = scan_single_feed(s.get("name", "Unknown"), feed_url, target_date, query=args.query)
                for it in feed_items:
                    u = it.get("url", "").strip()
                    if u and u not in seen_urls:
                        seen_urls.add(u)
                        all_items.append(it)

        # Near-midnight recovery evaluation if seed-url provided
        if args.seed_url and len(all_items) == 0:
            err_msg = f"Near-midnight recovery failed: No same-day primary sources found for query '{args.query}' on {target_date} matching seed {args.seed_url}."
            if args.json:
                print(json.dumps({
                    "status": "FAILED",
                    "message": err_msg,
                    "target_date": str(target_date),
                    "category": cat_key,
                    "query": args.query,
                    "seed_url": args.seed_url,
                    "total_items_found": 0
                }, ensure_ascii=False, indent=2))
            else:
                print(f"RECOVERY FAILED: {err_msg}", file=sys.stderr)
            sys.exit(1)

        dossier_sources: List[Dict[str, Any]] = []
        if args.init_dossier:
            dossier_path = Path(args.init_dossier)
            if dossier_path.exists() and not args.force:
                print(f"Error: Dossier file already exists at {dossier_path}. Use --force to overwrite.", file=sys.stderr)
                sys.exit(1)

            for it in all_items:
                dossier_sources.append({
                    "url": it["url"],
                    "role": "primary",
                    "outlet": it.get("outlet", ""),
                    "pub_date": it.get("pub_date_iso", str(target_date)),
                    "claims": [it.get("title", "")] if it.get("title") else []
                })

            if args.seed_url:
                dossier_sources.append({
                    "url": args.seed_url,
                    "role": "supplementary",
                    "outlet": "Seed Lead",
                    "pub_date": "",
                    "claims": ["Overnight seed source for context"]
                })

            topic_name = args.topic or (args.query if args.query else (all_items[0]["title"] if all_items else "Dossier Topic"))
            dossier = {
                "topic": topic_name,
                "date": str(target_date),
                "type": "news",
                "summary_of_event": f"Investigation dossier for {topic_name}",
                "key_entities": [args.query] if args.query else [],
                "timeline": [],
                "verified_facts": {
                    "numbers_and_metrics": [],
                    "dates_and_times": [],
                    "official_statements_and_quotes": []
                },
                "sources": dossier_sources,
                "contradictions": [],
                "status": "ready_for_kevrast_stage_1"
            }
            dossier_path.parent.mkdir(parents=True, exist_ok=True)
            with open(dossier_path, "w", encoding="utf-8") as f:
                json.dump(dossier, f, ensure_ascii=False, indent=2)

        if args.json:
            out_obj = {
                "status": "PASSED",
                "target_date": str(target_date),
                "timezone": "Europe/Istanbul",
                "category": cat_key,
                "query": args.query,
                "total_items_found": len(all_items),
                "items": all_items
            }
            if args.init_dossier:
                out_obj["init_dossier"] = str(args.init_dossier)
                out_obj["dossier_sources_count"] = len(dossier_sources)
            print(json.dumps(out_obj, ensure_ascii=False, indent=2))
        else:
            print("=" * 60)
            print("KEVKOLIN: Live News Radar & Feed Scanner")
            print(f"Date: {target_date} (Europe/Istanbul)")
            print(f"Category: {cat_key} | Query: {args.query or 'All'}")
            print("=" * 60)
            print(f"Found {len(all_items)} matching article(s) published today:")
            for it in all_items:
                print(f"  - [{it['outlet']}] {it['title']}")
                print(f"    URL: {it['url']}")
                print(f"    Published: {it['pub_date_iso']}")
            if args.init_dossier:
                print(f"\nDossier initialized at {args.init_dossier} with {len(dossier_sources)} sources.")
            print("=" * 60)

    elif args.init_dossier:
        dossier_path = Path(args.init_dossier)
        if dossier_path.exists() and not args.force:
            print(f"Error: Dossier file already exists at {dossier_path}. Use --force to overwrite.", file=sys.stderr)
            sys.exit(1)
        topic = args.topic or "New Topic"
        date_str = args.date or str(datetime.now(ISTANBUL).date())
        dossier = create_dossier_template(topic, date_str)
        dossier_path.parent.mkdir(parents=True, exist_ok=True)
        with open(dossier_path, "w", encoding="utf-8") as f:
            json.dump(dossier, f, ensure_ascii=False, indent=2)
        print(f"Initialized blank research dossier at {dossier_path}")

    elif args.new_dossier:
        date_str = args.date or str(datetime.now(ISTANBUL).date())
        dossier = create_dossier_template(args.new_dossier, date_str)
        print(json.dumps(dossier, ensure_ascii=False, indent=2))
    else:
        parser.print_help()

if __name__ == "__main__":
    main()
