🍽️ Studi Restaurant – Symfony LTS API
This repository contains a backend API developed as part of the STUDI courses, using the latest Symfony LTS version.

📄 License & Copyright
/*

Copyright (C) - All Rights Reserved.
Unauthorized copying of this repository, via any medium is strictly prohibited.
Proprietary and confidential.
Written by Thomas David thomas.david.ch@gmail.com.
 */


📚 Documentation
Before starting development, make sure your environment is properly configured:

IDE setup (PhpStorm recommended)
Useful plugins and shortcuts
Bash aliases and developer tools

Helpful resources:

https://github.com/webpro/awesome-dotfiles  
https://symfonycasts.com/screencast/phpstorm  
https://www.youtube.com/watch?v=_OEDoPMvNY4  
https://symfony.com/doc/6.2/the-fast-track/fr/index.html


⚙️ Requirements
Required tools:

PHP >= 7.2.5  
MySQL >= 8.0  
Symfony CLI  
Composer  
Git

Required PHP extensions:

Iconv  
JSON  
PCRE  
Session  
Tokenizer

Check your setup:
symfony check:requirements

👀 Preview
(Insert image: studi-restaurant-view.png)

🚀 Installation

Clone the repository

git clone https://github.com/thomasdavidch-afk/QuaiAntiqueRestaurantBack.gitcd QuaiAntiqueRestaurantBack  

Configure environment

cp .env .env.local  
Update your database configuration inside .env.local.

Install dependencies and setup database

composer installphp bin/console doctrine:database:createphp bin/console doctrine:migrations:migrate  

🔄 Workflow

Each course is associated with a specific branch  
The main branch always contains the most up-to-date version


▶️ Usage
Start the local server:
symfony server:start  
Useful commands:
bin/console debug:routerbin/console debug:container  

🚀 Deployment
Deploy using Platform.sh:
symfony project:set-remote [PROJECT_ID]symfony cloud:environment:push  

❌ Contributing
This is a private educational project. Contributions are not open.