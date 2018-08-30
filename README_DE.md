![CardGate](https://cdn.curopayments.net/thumb/200/logos/cardgate.png)

# CardGate Modul für VirtueMart 3.0.0 - 3.0.18

## Support

Dieses Modul is geeignet für VirtueMart version **3.0.0 - 3.0.18** mit den Joomla Versionen **2.5, 2.6 and 3.x**

## Vorbereitung

Um dieses Modul zu verwenden, sind Zugangsdaten zu **CardGate** notwendig.  
Gehen Sie zu [**Mein CardGate**](https://my.cardgate.com/) und fragen Sie Ihre **Site ID** und **Hash Key** an, oder kontaktieren Sie Ihren Accountmanager.

## Installation

1. Downloaden und entpacken Sie den aktuellsten [**source code**](https://github.com/cardgate/virtuemart3/releases) auf Ihrem Desktop.

2. Make a zip file of the **Cardgate allinoneinstaller** folder.

3. Gehen Sie zum **Adminbereich** Ihres Webshops und wählen Sie **Extensionmanager** aus **Extensions** aus.
 
4. Klicken Sie bei der Option **Datei hochladen** auf **Browse...**  
   Selektieren Sie den **CardGate allinoneinstaller.zip** Ordner.
   
5. Klicken Sie auf den **Datei hochladen & installlieren** Button.
  
## Configuration

1. Gehen Sie zum **Admin**-Bereich Ihres Webshops.

2. Selektieren Sie nun **Components, Virtuemart, Payment Methods**.

3. Klicken Sie auf den Button **Neu**. 

4. Wählen Sie das **Payment Method Information** Tab aus.

5. Füllen Sie den **Name** der Zahlungsmethode aus uns wählen Sie die gewünschte **Zahlungsmethode**.

6. Füllen Sie weitere Informationen in diesem Tab ein und klicken Sie auf Speichern.

7. Wählen Sie nun den **Konfigurations** Tab und klicken Sie auf speichern.

8. Fügen Sie die **Site ID** und den **Hash Key** ein, Diesen können Sie unter Webseiten bei [**Mein CardGate**](https://my.cardgate.com/) finden.

9. Fügen Sie weitere relevante Konfigurationsinformation ein und klicken Sie auf **Speichern** und **Schließen**.

10. Wiederholen Sie die Schritte 3 bis 9 für jede **Zahlungsmethode**, die Sie aktivieren möchten.

11. Gehen Sie zu [**Mein CardGate**](https://my.cardgate.com/), und wählen die gewünschten **Seiten** aus.

12. Füllen Sie nun bei **Technische Schnittstelle** die **Callback URL** ein, z.B.   
    **http://meinwebshop.com/index.php?option=com_cgp&task=callback**
    (Ersetzen Sie **http://meinwebshop.com** mit der URL Ihres Webshops.) 

13. Sorgen Sie dafür, dass Sie nach dem Testen **alle aktivierten Zahlungsmethoden** vom **Testmode** in **Livemode** und **Speichern** Sie die Einstellung. 
 
## Anforderungen

Keine weiteren Anforderungen.
