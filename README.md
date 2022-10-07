This extension need to searching, preparing and seending information about customer for your Freshdesk account. The extension is free, but you should install paid Freshdesk application (url to application page should be here)

**How to install the extension:**
1) Download actual version of the extension
2) Unpack it on your Magento 1 store
3) Re-login to Admin panel page
4) Go to the extension settings page (System > Configuration > MorfDev > Freshdesk)
5) Generate API Token (click on "Generate new token" button or input own string and save configuration)

**Possible problem**: CORS Policy is not configured on your Magento 1 store.
The application uses cross domain request (from Freshdesk domain to Magento 1 domain) to get information from your Magento 1 store. When CORS Policy is not configured, all cross domain requests will be blocked.

**How to configure CORS for Nginx**:
1) Add following code in Nginx config for Magento 1 store:
```
if ($request_method = 'OPTIONS') {
  add_header 'Access-Control-Allow-Origin' '*' always;
  add_header 'Access-Control-Allow-Methods' 'POST, GET, OPTIONS' always;
  add_header 'Access-Control-Allow-Headers' 'Accept,Authorization,Cache-Control,Content-Type,DNT,If-Modified-Since,Keep-Alive,Origin,User-Agent,X-Requested-With,X-Cache-Hash' always;
  ### Tell client that this pre-flight info is valid for 20 days
  add_header 'Access-Control-Max-Age' 1728000;
  add_header 'Content-Type' 'text/plain charset=UTF-8';
  add_header 'Content-Length' 0;
  return 204;
}
```
    
2) Restart Nginx

**How to configure CORS for Apache**:
1) Add following code in Apache config for Magento 1 store (You can add it in .htaccess file in Magento 1 root folder, when this file is allowed):
```
Header add Access-Control-Allow-Origin "*"
Header add Access-Control-Allow-Headers "origin, x-requested-with, content-type, authorization"
Header add Access-Control-Allow-Methods "PUT, GET, POST, DELETE, OPTIONS"
```
