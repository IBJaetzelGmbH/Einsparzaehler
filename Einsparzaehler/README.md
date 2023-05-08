# VariablenVergleich
Das Modul erstellt anhand einer oder mehreren Variablenpaaren ein Diagramm mit Puktewolken und mit Hilfe von einfacher Liniaren Regression eine Linie durch die Punktewolke. Das Diagramm kann als SVG oder PNG ausgegeben werden.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Erstellung eines Diagramms mit ein oder mehreren Wertepaaren 
* Ausgabe des Diagramms in SVG oder PNG 
* Ausgabe von Steigung, Y-Achsenabschnitt, Funktion und Bestimmtheitsmaß des Graphen

### 2. Voraussetzungen

- IP-Symcon ab Version 6.0

### 3. Software-Installation

* Über den Module Store das 'VariablenVergleich'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen

### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' kann das 'VariablenVergleich'-Modul mithilfe des Schnellfilters gefunden werden.  
	- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Werte:
Name              | Beschreibung
----------------- | ------------------
X Wert            | Variablenauswahl für die X-Achse
Y Wert            | Variablenauswahl für die Y-Achse
Punkt             | Farbauswahl für die Punktemakierung
Linie             | Farbauswal für die Linie 
Aggregationsstufe | Stufe wie detailiert die Daten aus dem Achiv geholt werden 

Diagramm Einstellungen:
Name                   | Beschreibung 
---------------------- | ----------
Achsen kleine Schritte | Gibt an in welchen Schritten kleine Makierungen sein sollen
Achsen große Schritte  | Gibt an in welchen Schritten die Makierung mit Beschriftung ist
Breite                 | Gibt an wie breit das Diagramm ist
Höhe                   | Gibt an wie hoch das Diagramm ist
Y - Min                | Gibt den minimalen Wert auf der Y-Achse an
Y - Max                | Gibt den maximalen Wert auf der Y-Achse an 
X - Min                | Gibt den minimalen Wert auf der X-Achse an 
X - Max                | Gibt den maximalen Wert auf der X-Achse an 
Digramm Format         | Gibt an ob das Diagramm als SVG oder PNG ausgegeben werden soll

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name             | Typ            | Beschreibung
---------------- | -------------- | ------------
Funktion         | String         | gibt die mathematische Funktion des Graphen wieder
b                | Float          | Beschreibt den y-Achsenabschnitt des Graphen
m                | Float          | Beschreibt die Steigung des Graphen 
Bestimmtheitsmaß | Float          | Beschreibt wie genau der Graph zu der Punktewolke passt
Startdatum       | Integer        | Datum, ab welchem die Punktewolke starten soll
Enddatum         | Integer        | Datum, bis zu welchem die Punktewolke geht
Chart            | String / Media | Gibt das Diagramm je nach Einstellung als PNG oder SVG


### 6. WebFront

Die Funktionalität, die das Modul im WebFront bietet.

### 7. PHP-Befehlsreferenz

`void LR_UpdateChart(integer $InstanzID);`
Generiert das Diagramm neu. Setzt es in das Ausgewählte Format und zeigt es im Konfigurationsformular an. 

Beispiel:
`LR_generateChart(12345);`

`array LR_UpdateChart(integer $InstanzID);`
Generiert das Diagramm neu und gibt es als SVG und PNG zurück.

Beispiel:
`LR_generateChart(12345);`

`void LR_Download(integer $InstanzID);`
Generiert das Diagramm neu und gibt eine Adresse aus. Über einen Button im Konfigurationsformular wird im Browser das Diagramm angezeigt.

Beispiel:
`LR_Download(12345);`