#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Kevkolin Source Catalog & Research Dossier Formatter
Manages the authoritative registry of verified Kurdish, regional, and international media sources
and formats structured Research Dossiers for downstream processing by Kevrast and Kevanger.
"""

import sys
import json
import argparse
from typing import Dict, List, Any

VERIFIED_SOURCES = {
    "kurdish_and_regional": [
        {"name": "Rûdaw", "urls": ["https://rudaw.net/kurmanci", "https://rudaw.net/english", "https://rudaw.net/turkish", "https://rudaw.net/sorani", "https://www.rudawarabia.net/arabic"]},
        {"name": "Kurdistan24", "urls": ["https://www.kurdistan24.net/kmr", "https://www.kurdistan24.net/ckb", "https://www.kurdistan24.net/en", "https://www.kurdistan24.net/tr", "https://www.kurdistan24.net/ar"]},
        {"name": "Dengê Amerîka (VOA Kurdî)", "urls": ["https://www.dengiamerika.com"]},
        {"name": "964media", "urls": ["https://964media.com"]},
        {"name": "NRT TV", "urls": ["https://nrttv.com"]},
        {"name": "Shafaq News", "urls": ["https://shafaq.com"]},
        {"name": "North Press Agency (NPA)", "urls": ["https://npasyria.com"]},
        {"name": "Hawar News (ANHA)", "urls": ["https://hawarnews.com"]},
        {"name": "Bianet Kurdî", "urls": ["https://bianet.org/kurdi"]},
        {"name": "Hengaw Human Rights Organization", "urls": ["https://hengaw.net"]},
        {"name": "Mezopotamya Ajansı (MA)", "urls": ["https://mezopotamyaajansi44.com/kr"]},
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
            {"name": "Anadolu Ajansı (AA)", "url": "https://www.aa.com.tr"},
            {"name": "T24", "url": "https://t24.com.tr"},
            {"name": "Gazete Duvar", "url": "https://www.gazeteduvar.com.tr"}
        ]
    },
    "iran": {
        "quota": 1,
        "sources": [
            {"name": "IRNA", "url": "https://en.irna.ir"}
        ]
    }
}

def create_dossier_template(topic: str, date: str) -> Dict[str, Any]:
    return {
        "topic": topic,
        "date": date,
        "summary_of_event": "",
        "key_entities": [],
        "timeline": [],
        "verified_facts": {
            "numbers_and_metrics": [],
            "dates_and_times": [],
            "official_statements_and_quotes": []
        },
        "source_evidence": [],
        "contradictions_or_variances": [],
        "status": "ready_for_kevrast_stage_1"
    }

def main():
    parser = argparse.ArgumentParser(description="Kevkolin Source Catalog & Dossier Formatter")
    parser.add_argument("--list-sources", action="store_true", help="List all verified sources and quotas")
    parser.add_argument("--json", action="store_true", help="Output source list in JSON")
    parser.add_argument("--new-dossier", help="Generate a blank structured research dossier for a topic")
    parser.add_argument("--date", default="", help="Date string for the dossier")

    args = parser.parse_args()

    if args.list_sources:
        if args.json:
            print(json.dumps(VERIFIED_SOURCES, ensure_ascii=False, indent=2))
        else:
            print("=" * 60)
            print("KEVKOLIN: Authoritative Verified Source Registry")
            print("=" * 60)
            for region, data in VERIFIED_SOURCES.items():
                print(f"\n[{region.upper().replace('_', ' ')}]")
                if isinstance(data, list):
                    for s in data:
                        urls = ", ".join(s.get("urls", []))
                        print(f"  - {s['name']}: {urls}")
                elif isinstance(data, dict):
                    print(f"  Quota: {data.get('quota', 'N/A')}")
                    for s in data.get("sources", []):
                        extra = f" [{s.get('country')}]" if "country" in s else ""
                        print(f"  - {s['name']}{extra}: {s['url']}")
            print("=" * 60)
    elif args.new_dossier:
        dossier = create_dossier_template(args.new_dossier, args.date)
        print(json.dumps(dossier, ensure_ascii=False, indent=2))
    else:
        parser.print_help()

if __name__ == "__main__":
    main()
