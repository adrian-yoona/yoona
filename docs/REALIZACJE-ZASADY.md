# Opisy realizacji: zakres i zasady

Przejrzano 153 wpisy typu `post` z eksportu witryny z 15 października 2026 r. Przygotowano **150 krótkich opisów realizacji** w [project-guides.json](../plugin/yoona-seo/content/project-guides.json). Dane stanowią osobną warstwę prezentacji. Nie zmieniają historycznej paczki SEO `changes.json`, tytułów wpisów ani metadanych Rank Math.

## Co zawierają opisy

Każdy opis podaje przedmiot pracy, klienta lub rodzaj obiektu, jeżeli został jednoznacznie wskazany, zakres potwierdzony w istniejącym tytule lub tekście oraz link do powiązanej oferty. **103 opisy** mają potwierdzoną miejscowość lub obszar, a **36** zawiera konkretny materiał lub komponent. Przy pozostałych nie dopisano nieznanych danych.

Przykłady faktów wykorzystanych w opisach:

- DELTA: baner 12 × 6 m i ekspozycja przy trasie w kierunku Elbląga. Kierunek trasy nie został zamieniony w twierdzenie o lokalizacji w Elblągu.
- Dodge RAM 1500: zmiana czarnego koloru na srebrno-zielony i nazwa folii AVERY SWF ColorFlow Gloss Urban Jungle Silver/Green.
- ALIGATOR: oddzielono realizację liter 3D w CH Victor od kasetonu z wystającą pleksi w Galerii Warmińskiej.
- BCLINIQUE: trzy rollupy oraz wydruki do trzech ścianek tekstylnych DXT 100 × 200 cm.
- System pylonów: 20 podświetlanych nośników. Nie przypisano mu miejscowości na podstawie regionalnych słów kluczowych występujących w ogólnym tekście.

Nie dopisano wymyślonych terminów wykonania, kosztów, wzrostów sprzedaży, efektów pozycjonowania, opinii klientów, certyfikatów, gwarancji ani adresów. Nie utożsamiano listy materiałów dostępnych w ogólnej ofercie z materiałami użytymi w konkretnej realizacji. Ogólna deklaracja „działamy w Olsztynie i Ostródzie” nie potwierdza lokalizacji projektu.

W eksporcie występują nagłówki skopiowane z innych projektów, m.in. BCLINIQUE przy realizacjach IRJADOS i AREL oraz reklama na rusztowaniu przy kasetonie ANANASEK. Nowe opisy nie przenoszą tych nagłówków do informacji o projekcie. Dwie rozbieżne pisownie miejscowości przy AMEX BĄCZEK nie zostały rozstrzygnięte przez dopisanie lokalizacji.

## Pominięte wpisy

| Ścieżka | Powód |
| --- | --- |
| `/drukarnia-wielkoformatowa-olsztyn-ostroda/` | Ogólny artykuł o usłudze, bez jednoznacznej pojedynczej realizacji. |
| `/agencja-reklamowa-olsztyn-yoona/` | Ogólny artykuł o agencji, bez konkretnego projektu do opisania. |
| `/folia-szroniona-billenium/` | Pusta treść; sam tytuł nie wystarcza do rozszerzenia opisu projektu. |

Pominięcie przewodnika nie oznacza usunięcia wpisu ani zdjęcia wyróżniającego. Eksport nie zawiera pełnego stanu wszystkich załączników, dlatego brak zdjęcia w treści nie dowodzi braku fotografii przypisanej w WordPressie.

## Struktura danych i kontrola aktualności

Plik jest obiektem JSON, którego kluczami są kanoniczne ścieżki wpisów. Wartość zawiera:

| Pole | Znaczenie |
| --- | --- |
| `title` | Oryginalny tytuł wpisu, służący identyfikacji projektu. |
| `summary` | Krótki opis oparty na potwierdzonych faktach. |
| `scope` | Lista wykonanych elementów lub prac. |
| `service_path` | Ścieżka rzeczywiście istniejącej strony oferty. |
| `source_sha256` | Dozwolone skróty całej źródłowej treści wpisu. |
| `location` | Opcjonalna, potwierdzona miejscowość lub obszar. |
| `materials` | Opcjonalne, potwierdzone materiały lub komponenty. |

Wszystkie 150 rekordów ma skrót oryginalnego `post_content`. Dziesięć ma dodatkowo drugi skrót: stan po zastosowaniu istniejących, zatwierdzonych zmian SEO. Przy obliczaniu tego wariantu sprawdzono skróty każdego zmienianego modułu Divi i zachowano wszystkie bajty poza zatwierdzonymi fragmentami. Stan po SEO nie jest odgadywany ani zastępowany skrótem samego tekstu widocznego na ekranie.

Warstwa prezentacji powinna wyświetlać opis tylko przy zgodności ścieżki i jednego z dozwolonych skrótów całej treści. Zmieniona ręcznie realizacja wymaga ponownego sprawdzenia faktów i dodania nowego, sprawdzonego skrótu. Nie należy usuwać tej kontroli ani dopasowywać wpisu samym tytułem. Stan starego tekstu lub stan po SEO nie uprawnia do zmiany zdjęć, formularzy ani innych elementów wpisu.

## Zdjęcia i tekst historyczny

W źródłowych 153 wpisach znaleziono 458 znaczników HTML `img`, 50 modułów `et_pb_image` i jeden moduł `et_pb_gallery`. To liczby wystąpień, a nie liczba unikalnych fotografii. Występują też 132 bloki galerii Gutenberga i 12 kontenerów `divi/placeholder`. Nie znaleziono znaczników `form`, `iframe`, `video`, `audio` ani shortcode formularzy kontaktowych; brak w tym eksporcie nie zwalnia z ochrony później dodanych elementów.

Przy zastępowaniu długiego tekstu nowym opisem należy zachować fotografie, galerie i linki do ich większych wersji. Ukrywanie starego tekstu może działać wyłącznie dla rozpoznanej, sprawdzonej wersji źródła. Przy niezgodności skrótu pozostaje zwykła prezentacja istniejącego wpisu.

## Dalsze uzupełnianie

Przy następnych realizacjach warto od razu zapisywać: miejscowość, faktyczne zadanie, wykonane elementy, konkretny materiał, własne zdjęcia oraz pasującą usługę. Wymiary, termin, cenę lub rezultat biznesowy podaje się dopiero wtedy, gdy firma dysponuje potwierdzeniem odnoszącym się do tego projektu. Nie trzeba sztucznie wydłużać krótkiej realizacji ogólną treścią o całej ofercie.

Opracowano 17 października 2026 r. na podstawie przekazanego eksportu. Dokument nie potwierdza instalacji zmian na produkcyjnej witrynie ani ponownego sprawdzenia faktów poza przekazanym źródłem.
