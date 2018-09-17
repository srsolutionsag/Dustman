Dustman
========
Dustman is a simple Cron plugin which moves old ILIAS courses and groups into the trash. You can decide for the max. age
of a course/group after this time it gets moved automatically to the trash.

### Important Notice
**Use of this software happens solely on your own risk!!**

Please test the software extensively on your test environment.

If you have questions, studer + raimann ag can offer paid support services: info@studer-raimann.ch

### Features
* Excluding Methods:
  * Specify some categories who should be excluded.
  * Specify some keyword-tags which should be excluded.
* Get an automated e-mail x days before the object gets moved to the trash.

### Installation
You need ILIAS >= 4.4 to run this plugin.

In order to install the Dustman plugin go into ILIAS root folder and use:

```bash
mkdir -p Customizing/global/plugins/Services/Cron/CronHook
cd Customizing/global/plugins/Services/Cron/CronHook
git clone https://github.com/studer-raimann/Dustman.git
```

### ILIAS Plugin SLA

Wir lieben und leben die Philosophie von Open Source Software! Die meisten unserer Entwicklungen, welche wir im Kundenauftrag oder in Eigenleistung entwickeln, stellen wir öffentlich allen Interessierten kostenlos unter https://github.com/studer-raimann zur Verfügung.

Setzen Sie eines unserer Plugins professionell ein? Sichern Sie sich mittels SLA die termingerechte Verfügbarkeit dieses Plugins auch für die kommenden ILIAS Versionen. Informieren Sie sich hierzu unter https://studer-raimann.ch/produkte/ilias-plugins/plugin-sla.

Bitte beachten Sie, dass wir nur Institutionen, welche ein SLA abschliessen Unterstützung und Release-Pflege garantieren.

### Contact
info@studer-raimann.ch  
https://studer-raimann.ch  



