Salesforce REST/PHP Example
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Salesforce just released the REST version of their API, and while there's a Java example, 
there's no sample code for other languages. Since I'll be calling it from PHP, I used their 
documentation to build my own sample code. The source is available at 
http://github.com/petewarden/salesforce_restphp_example/ and you can see a live version running 
at https://www.openheatmap.com/labs/salesforce_restphp_example/. The code demonstrates how to 
authenticate, gets an access token and then calls the API to grab information about the sales 
accounts for the current user.

To use the API at all, you'll need a server setup up with an SSL certificate and https since it 
requires a secure connection to use OAuth 2.0 for authentication. I found this guide from 
Ubuntu useful in getting that set up, and bought my certificate from GoDaddy.

With that sorted out, go to http://developer.force.com/join to create a Developer Edition 
salesforce account. You'll also want to sign up for the REST API preview beta program (though 
they're currently experiencing a few technical hiccups with the process).

Next, navigate to the Setup link in the top-right corner of the page, then click on Develop, 
then Remote Access. Pick an application name, and add the location where you'll be uploading 
the example index.php file as the callback URL. I also checked the No user approval required 
box even though I'm not sure exactly what it does! After you've saved you should see a screen 
giving you your access credentials. Copy the Consumer Key, Consumer Secret and callback URL 
values into the start of your copy of the index.php sample code and then upload it to the 
server.

Now point your web browser at the address you uploaded the sample code to. The first time 
through it should redirect you to a login page on the Salesforce site, ask you whether you want 
to let the application access your data, and then send you back to the sample code location. If 
all goes well, you should see a list of your sales accounts

Pete Warden <pete@petewarden.com> - http://petewarden.typepad.com