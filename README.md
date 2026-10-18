# YOONA — wygląd, treści i SEO 3.4.2

**[Pobierz wtyczkę yoona-seo-v3.4.2.zip](https://github.com/adrian-yoona/yoona/raw/refs/heads/yoona-seo-download-20261015/downloads/yoona-seo-v3.4.2.zip)**. ZIP jest archiwum instalacyjnym WordPressa.

## Kompaktowa oferta i góra na telefonie — 3.4.2

Panele mają mniejsze nagłówki Manrope, czytelne listy i krótsze odstępy. Na komputerze tworzą układ trzech i dwóch bloków, na mniejszych ekranach przechodzą do dwóch i jednej kolumny. Wstęp jest szerszy, bez wielkiego pustego miejsca obok tekstu.

Telefon pokazuje jeden nagłówek ze zdjęciem w tle, ciemnym gradientem i dwoma przyciskami. Usunięto osobny wysoki blok zdjęcia pod tekstem i dekoracyjne podpisy. Zachowano wszystkie teksty i linki. Zakres CSS ograniczono do nowych paneli i góry ofert, z zachowaniem naprawy galerii i dołu 3.4.1.

## Naprawa galerii i dolnych sekcji — 3.4.1

Wersja 3.4 wyjmowała moduły mediów z oryginalnych rodziców Divi. Galerie traciły układ, a część dolnych nagłówków, przycisków i FAQ znikała. W 3.4.1 oferty renderują cały oryginalny układ, z sekcjami, wierszami, kolumnami i numeracją modułów. Nowe opisy zastępują wyłącznie dokładnie rozpoznany tekstowy wstęp. Wprowadzenia flag i totemów zachowują także swoje zdjęcia i przyciski. Stary blok kontaktu ze zdjęciem pokrętła nadal jest usuwany.

Naprawa działa podczas wyświetlania; nie wymaga ponownego zapisu SEO ani tworzenia sześciu stron drugi raz. Po aktualizacji wyczyść cache w panelu wtyczki oraz LiteSpeed Cache.

## Co zawiera aktualizacja

- 41 ofert w krótszych, uporządkowanych sekcjach: zastosowanie, materiały, realizacja, cena, dane do wyceny i pytania. CNC oraz markizy SELT mają rozwinięty zakres.
- Sześć nowych usług: frezowanie plexi, dibondu i elementów reklamowych; markizy tarasowe, wolnostojące DUO i znakowanie markiz. Publikuje je osobny przycisk administratora.
- 150 krótkich opisów realizacji opartych na potwierdzonych faktach; 103 miejscowości i 36 list materiałów. Późniejsze edycje chroni kontrola źródła.
- Wszystkie publiczne kategorie na głównej i jasne menu **Pełna oferta**. Osiem kart ma zdjęcia, pozostałe usługi tworzą kompaktową, zawsze widoczną listę.
- Równe kontakty Olsztyn/Ostróda w nagłówku, stopce oraz na stronie Kontakt, również przy 320 px. Mniejsze odstępy i spokojniejsza typografia.
- Zachowane zdjęcia, zagnieżdżone galerie i formularze. Galeria realizacji nadal pokazuje 30 prac na stronę, trzy kolumny na komputerze, samo zdjęcie i tytuł. Usuwanie starego bloku z pokrętłem pozostaje aktywne.
- Dwa gotowe szkice artykułów o markizach, plan 10 fraz lokalnych, projekty wizytówek i reklam Ads oraz instrukcja pomiaru kontaktów.

## Wgranie w WordPressie

1. Wykonaj pełną kopię witryny. Wybierz **Wtyczki → Dodaj nową → Wyślij wtyczkę**, wgraj ZIP i zastąp poprzednią Yoona SEO. Rank Math pozostaw aktywny.
2. Otwórz **Narzędzia → Yoona SEO**, sprawdź **wersję 3.4.2** i kliknij **Odśwież wygląd i wyczyść cache**. Jeśli nowy wygląd był wyłączony, włącz go w tym panelu.
3. Kliknij **Dodaj 6 podstron CNC i markiz**. Przycisk tworzy brakujące strony wraz z tytułami i opisami Rank Math. Zajęte adresy są pomijane, powtórzenie nie tworzy kopii. Historia pozwala przenieść do kosza tylko całkowicie niezmienione strony utworzone tą operacją.
4. Sprawdź główną, Kontakt, ofertę i zdjęcia realizacji na telefonie oraz komputerze. Cache hostingu/CDN może wymagać osobnego czyszczenia.

**Do tej aktualizacji nie trzeba ponownie zapisywać starego pakietu SEO.** Dotychczasowy pakiet 200 metadanych i 896 zmian modułów zachowano. Jeżeli nie był jeszcze zapisany, nadal ma osobny podgląd, przycisk zapisu, kopie i kontrolę konfliktów.

## Treści i edycja

Nowe opisy istniejących ofert należą do warstwy wyglądu. Zapisane treści Divi pozostają w bazie; wyłączenie wyglądu przywraca dotychczasowe szablony i treść. Redakcję nowych opisów zawiera [service-guides.json](plugin/yoona-seo/content/service-guides.json). Rozpoznany wstęp ma kontrolę dokładnego źródła. Ręcznie zmieniony wstęp zachowuje swój pełny układ i tekst; nowa prezentacja nie nakłada na niego nieaktualnego opisu. Pozostałe sekcje Divi, w tym galerie i cały dół, są zachowane.

Sześć nowych stron ma również normalną treść WordPressa działającą po wyłączeniu wtyczki. Późniejsza ręczna zmiana ich treści lub tytułu powoduje użycie standardowego widoku, aby zachować nową pracę. Opisy realizacji działają tylko przy zgodnej, sprawdzonej wersji źródła; zmienione wpisy zachowują dotychczasową treść.

## Google i analiza

- [Plan lokalnego SEO i 10 fraz](docs/PLAN-SEO-LOKALNE.md).
- [Zakres analizy eksportów](docs/ANALIZA-EKSPORTOW.md). Surowe raporty, ruch i dane kampanii pozostają poza repozytorium.
- [Site Kit, GA4 i projekt Ads](docs/GOOGLE-POMIAR-I-ADS.md) oraz [pliki reklam](docs/google-ads).
- [Treści wizytówek i poleceń](docs/WIZYTOWKI-GOOGLE-I-POLECENIA.md).
- [Dwa artykuły o markizach](docs/ARTYKULY-MARKIZY.md) i [strategia treści](docs/STRATEGIA-TRESCI-2026-10-17.md).
- [Zasady opisów realizacji](docs/REALIZACJE-ZASADY.md).

Wtyczka przekazuje `yoona_contact_click` i `yoona_quote_click` przez istniejące `gtag` Site Kit po rozpoznanej zgodzie analitycznej CookieYes lub WP Consent API. Brak lub odmowa zgody pomija zdarzenie. Nie ładuje drugiego tagu i nie przekazuje numeru telefonu, e-maila, wartości formularza ani parametrów URL. Gdy działa wyłącznie istniejący GTM, dostępny jest osobny wariant warstwy danych, wymagający konfiguracji kontenera. Jeden klik korzysta z jednej drogi. Kliknięcie kontaktu nie jest potwierdzonym leadem.

Eksporty pozwalają analizować dane, lecz nie dają dostępu do kont Google. Nie zmieniono kampanii, budżetów, ustawień Analytics, Search Console ani wizytówek. Nie wysłano wiadomości do klientów. Odczyt publicznych wyników, witryny i zdjęć blokował proxy, więc porównanie konkurentów, indeksacja i produkcyjne Core Web Vitals pozostają niezweryfikowane. Zrzutu audytu cyber_Folks nie było w przekazanych załącznikach.

## Testy i rozwój

[Wyniki i ograniczenia](docs/WYNIKI-TESTOW.md) · [odtworzenie testów](tests/README.md) · [wcześniejszy audyt](audit/AUDYT-SEO-YOONA.md).

```sh
python3 -m unittest discover -s tests -p 'test_audit*.py' -v
node tests/integration-batch-ui.cjs
node tests/test-admin-batches.cjs
python3 tools/package_plugin.py
```

Testy WordPressa wymagają Dockera; szczegóły w tests/README.md. Licencjonowany Divi nie jest dołączony: testy używają jawnych odpowiedników callbacków. Zdjęcia produkcyjne w testach geometrii zastępują oznaczone obrazy lokalne. Publikacja ZIP-a na GitHubie nie instaluje go na yoona.pl i nie potwierdza wyników SEO.
