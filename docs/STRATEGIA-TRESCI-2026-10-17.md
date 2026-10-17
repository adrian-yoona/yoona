# Treści ofertowe YOONA — 17 października 2026

## Co przygotowano

Plik `plugin/yoona-seo/content/service-guides.json` zawiera 47 odrębnych poradników ofertowych: 41 istniejących usług i sześć podstron tematycznych CNC oraz markiz. Łącznie to 11 696 słów; pojedynczy poradnik ma 231–290 słów. Krótkie sekcje odpowiadają na pytania potrzebne do decyzji i wyceny, zamiast wielokrotnie powtarzać tę samą frazę oraz nazwę miejscowości.

Źródłem zakresu istniejących usług był prywatny eksport strony z 15 października 2026. Nowe informacje o markizach SELT, wariantach przyściennych i dwustronnych DUO, znakowaniu, odbiorcach indywidualnych i gastronomicznych oraz obsłudze całej Polski wynikają z tekstu przekazanego przez właściciela. Eksport pozostaje poza repozytorium. Nowy plik nie zastępuje historycznego pakietu SEO `changes.json` i nie zawiera surowych modułów Divi.

Każdy poradnik ma tytuł, krótki wstęp, omówienie wyboru, materiały lub warianty, zastosowania, trzy etapy realizacji, czynniki ceny, listę danych do wyceny, odpowiedzi na dwa lub trzy pytania oraz trzy powiązane usługi. Treści są zwykłym tekstem; szablon odpowiada za nagłówki i prezentację. Nie zawierają cen, wymyślonych realizacji, obietnic pierwszej pozycji ani niepotwierdzonych terminów i parametrów.

## Struktura CNC

Istniejąca strona `/frezowanie-cnc/` pełni funkcję głównej oferty. Zachowuje dotychczasowy adres. Objaśnia zakres obróbki, wymagania dokumentacji oraz współpracę z innymi producentami reklam.

| Nowa podstrona | Odrębna potrzeba odbiorcy |
| --- | --- |
| `/frezowanie-plexi/` | Rodzaj PMMA, kontur, otwory, przeznaczenie i wygląd krawędzi. |
| `/frezowanie-dibondu/` | Formatki, grubość płyty, otwory oraz specyfikacja elementów składanych. |
| `/frezowanie-elementow-reklamowych/` | Komponenty do liter, tablic, szyldów i kasetonów oraz podział prac między wykonawcami. |

Nowe strony odsyłają do oferty głównej i właściwych produktów. Nie powstają kopie typu „ta sama usługa w innym mieście”. Możliwość obróbki zależy od konkretnej dokumentacji, materiału i geometrii; treści nie deklarują zakresu tolerancji, wymiarów maszyny ani dowolności materiałowej. Frezowanie jest odróżnione od polerowania i dalszego wykańczania.

## Struktura markiz

Istniejąca strona `/markizy-z-logo/` łączy przegląd wariantów SELT i zastosowań. Nie deklaruje statusu autoryzowanego partnera ani własnych parametrów producenta.

| Nowa podstrona | Odrębna potrzeba odbiorcy |
| --- | --- |
| `/markizy-tarasowe/` | Dobór markizy do budynku, podłoża, szerokości tarasu i potrzeb użytkownika. |
| `/markizy-wolnostojace-duo/` | Planowanie wolnostojącej konstrukcji dwustronnej oraz miejsca jej ustawienia. |
| `/znakowanie-markiz/` | Projekt logo, zgodność z tkaniną i ocena możliwości oznakowania istniejącej markizy. |

Oferta uwzględnia klientów indywidualnych, restauracje, kawiarnie i hotele. Opis obsługi całej Polski zawiera województwa pomorskie, mazowieckie, kujawsko-pomorskie i podlaskie jako przykłady obszaru zapytań. Dostawa i montaż są ustalane dla adresu zamówienia; nie dopisano fikcyjnych oddziałów. Szczegółowe ograniczenia użytkowania wynikają z dokumentacji wybranego modelu SELT.

Zdjęcia sześciu nowych podstron wskazują istniejące zasoby oferty nadrzędnej przez `image_source_path`. Nie są nowymi zdjęciami konkretnych realizacji lub modeli. Zdjęć producenta nie pobrano ani nie przedstawiono jako własnych. Jeśli zostaną udostępnione materiały SELT z prawem publikacji i określeniem modelu, można zastąpić nimi grafikę poglądową.

## Propozycja krótkiego wstępu lokalnego na głównej

**Agencja reklamowa, która projektuje i produkuje**

YOONA to agencja reklamowa obsługująca Olsztyn, Ostródę oraz firmy z Warmii i Mazur. Łączymy projekt, druk, produkcję i montaż, żeby oznakowanie lokalu, pojazdów i materiałów firmowych tworzyło spójną całość.

Tworzymy reklamy świetlne, kasetony reklamowe, tablice i litery 3D. Wykonujemy oklejanie samochodów oraz szyb, zmianę koloru samochodu, banery reklamowe i druk wielkoformatowy. Ofertę uzupełniają neony, ledony, beczki reklamowe oraz frezowanie CNC elementów na wymiar.

To propozycja redakcyjna do układu strony głównej. Nie należy dodawać jej ponownie, jeśli aktualny szablon przedstawia już ten zakres w swoim wstępie i kartach usług.

## Realizacje: uzupełniać tylko ustalone fakty

Nie dopisano automatycznie materiałów, lokalizacji ani efektów biznesowych do dawnych realizacji. Nazwa lub zdjęcie nie są wystarczającym dowodem użytego materiału czy wzrostu sprzedaży. Przy uzupełnianiu konkretnej pracy należy potwierdzić: miejscowość, cel zlecenia, wykonane elementy, zastosowany materiał lub technologię i zakres montażu. Zakończenie powinno prowadzić do jednej właściwej usługi. W zbiorczej galerii pozostaje zdjęcie oraz tytuł.

## Dalsza praca redakcyjna

Dwa gotowe szkice poradników są w `docs/ARTYKULY-MARKIZY.md`. Mają odrębne cele: wybór konstrukcji i przygotowanie znakowania. Są przygotowane do redakcji i publikacji, ale zapis dokumentu w GitHubie nie oznacza, że artykuły pojawiły się w WordPressie. Nie należy tworzyć kopii tych tekstów osobno dla każdego województwa.

Ocena skuteczności powinna łączyć dane Search Console dla stron i grup zapytań z pomiarem rzeczywistych zapytań o wycenę. Wzrost samej liczby słów nie jest celem. Najpierw warto rozwijać usługi z potwierdzonym popytem oraz realizacje, dla których firma ma własne zdjęcia i dokumentację.
