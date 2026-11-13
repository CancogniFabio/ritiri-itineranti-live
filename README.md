# Ritiri Itineranti · sito live

Sito statico pubblicato su **ritiriitineranti.it** (hosting GreenGeeks, deploy da GitHub).
Il mock-up con le idee future resta nel repository `ritiri-itineranti`.

- Partenze, quote e insegnanti: `data.js`
- Moduli: inviano a `send.php`, che spedisce a ciao@ritiriitineranti.it (funziona solo su GreenGeeks, non sull'anteprima GitHub Pages)
- SEO: `sitemap.xml`, `robots.txt`, `llms.txt`, meta e dati strutturati in ogni pagina; analisi in `SEO.md`
- `.htaccess`: HTTPS, dominio senza www, pagina 404, cache
- `.cpanel.yml`: in cPanel > Git Version Control premi "Update from Remote" e poi "Deploy HEAD Commit" per pubblicare le modifiche su ritiriitineranti.it
