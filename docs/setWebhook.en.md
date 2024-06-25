# How to set up a Webhook

Use the Telegram API setWebhook method to set the webhook:

**If you using the browser address bar for setWebhook**  
https://api.telegram.org/bot{token}/setWebhook?url=https://{example.com}/bots/hook.php?bot={botname}  

Specify the API key of your bot and the url path to your hook file  
Also specify the additional parameter ?bot={botname}. This will allow using one shared hook file for multiple bots  

**Example setWebhook**  
https://api.telegram.org/bot1234567890:BBIJKL80DFMMOPQy-Yfj8Zq6Lm_78Vb2x3W/setWebhook?url=https://example.com/bots/hook.php?bot=botnameBot  