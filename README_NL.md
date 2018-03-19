![CardGate](https://cdn.curopayments.net/thumb/200/logos/cardgate.png)

# CardGate module voor VirtueMart 3.0.0 - 3.0.18

[![Total Downloads](https://img.shields.io/packagist/dt/cardgate/virtuemart3.svg)](https://packagist.org/packages/cardgate/virtuemart3)
[![Latest Version](https://img.shields.io/packagist/v/cardgate/virtuemart3.svg)](https://github.com/cardgate/virtuemart3/releases)
[![Build Status](https://travis-ci.org/cardgate/virtuemart3.svg?branch=master)](https://travis-ci.org/cardgate/virtuemart3)

## Support

Deze Extensie is geschikt voor VirtueMart versie **3.0.0 - 3.0.18** met Joomla versie **2.5, 2.6 and 3.x**

## Voorbereiding

Voor het gebruik van deze module zijn CardGate gegevens nodig.  
Bezoek hiervoor [Mijn CardGate](https://my.cardgate.com/) en haal je Site ID and Hash Key op,  
of neem contact op met je accountmanager.  

## Installatie

1. Download het [Mijn CardGate](https://my.cardgate.com/) bestand.

2. Ga naar het **admin** gedeelte van je webshop en selecteer **Extensiebeheer** uit het **Extensies** menu.

3. Bij de optie **Upload pakketbestand** klik op de knop **Bladeren...**   
   Selecteer het **CardGate allinoneinstaller.zip** bestand.
   
4. Klik op de knop **Uploaden & Installeren**.  
   (Het is ook mogelijk om de plug-in te unzippen en via **FTP** te uploaden en dan te installeren.)
  
## Configuratie

1. Log in op het **admin** gedeelte van je webshop.

2. Kies nu **Componenten, Virtuemart, Payment Methods**.

3. Klik op de knop **Nieuw**.

4. Kies het **Payment Method Information** tabblad.

5. Vul de **naam** van de betaalmethode in en kies de gewenste **betaalmethode**.

6. Vul de andere details in op dit tabblad en klik op **Opslaan**.

7. Kies nu het **Configuration** tabblad.

8. Vul de **Site ID** en de **Hash Key (Codeersleutel)** in, deze kun je vinden bij **Sites** op [Mijn CardGate](https://my.cardgate.com/).

9. Vul de andere relevante configuratie informatie in en klik op **Opslaan** en **Sluiten**.

10. Herhaal de **stappen 3 tot en met 9** voor iedere **betaalmethode** die je wenst te activeren.

11. Ga naar [My Cardgate](https://my.cardgate.com/), kies **Sites** en selecteer de juiste site.

12. Vul bij **Technische Koppeling** de **Callback URL** in, bijvoorbeeld:  
    **http://mijnwebshop.com/index.php?option=com_cgp&task=callback**  
   (Vervang **http://mijnwebshop.com** met de URL van je webshop)  

13. Zorg ervoor dat je na het testen **alle betaalmethoden** omschakelt van **Test Mode** naar **Live mode** en sla het op (**Save**).
 
## Vereisten

Geen verdere vereisten.
