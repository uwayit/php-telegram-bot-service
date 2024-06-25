# Як встановити Webhook

Скористайтесь методом setWebhook API Telegram для встановлення webhook:  

**Якщо ви використовуєте адресний рядок браузера для setWebhook**  

https://api.telegram.org/bot{token}/setWebhook?url=https://{example.com}/bots/hook.php?bot={botname}  

Вкажіть API ключ вашого бота та url шлях до Вашого hook файлу  
Вкажіть також додатковий параметр ?bot={botname}. Це дозволить використовувати один спільний файл hook для декількох ботів  

**Example setWebhook**  
https://api.telegram.org/bot1234567890:BBIJKL80DFMMOPQy-Yfj8Zq6Lm_78Vb2x3W/setWebhook?url=https://example.com/bots/hook.php?bot=botnameBot  