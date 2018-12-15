# PHP-DDNS

The term is used to describe two different concepts. The first is "dynamic DNS updating" which refers to systems that are used to update traditional DNS records without manual editing. These mechanisms are explained in RFC 2136, and use the TSIG mechanism to provide security. The second kind of dynamic DNS permits lightweight and immediate updates often using an update client, which do not use the RFC2136 standard for updating DNS records. These clients provide a persistent addressing method for devices that change their location, configuration or IP address frequently. 

### Usage

To update IP address on DNS record, use task scheduler like Windows Task Scheduler or Cron Job

### Command Line

**Linux Version**

```sh
/bin/php -q /var/ddns/ddns.php
```

**Windows Version**

```sh
D:\xampp\php\php-cgi.exe -q D:\xampp\htdocs\ddns\ddns.php
```
