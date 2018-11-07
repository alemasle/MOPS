```
sudo hping3 35.35.35.1 -p 80 -S --tcp-timestamp -c 4
HPING 35.35.35.1 (wlan0 35.35.35.1): S set, 40 headers + 0 data bytes
len=56 ip=35.35.35.1 ttl=59 DF id=0 sport=80 flags=SA seq=0 win=28960 rtt=7.8 ms
  TCP timestamp: tcpts=4294899747

len=56 ip=35.35.35.1 ttl=59 DF id=0 sport=80 flags=SA seq=1 win=28960 rtt=7.5 ms
  TCP timestamp: tcpts=4294899997
  HZ seems hz=100
  System uptime seems: 497 days, 2 hours, 16 minutes, 39 seconds

len=56 ip=35.35.35.1 ttl=59 DF id=0 sport=80 flags=SA seq=2 win=28960 rtt=7.3 ms
  TCP timestamp: tcpts=4294900247
  HZ seems hz=100
  System uptime seems: 497 days, 2 hours, 16 minutes, 42 seconds

len=56 ip=35.35.35.1 ttl=59 DF id=0 sport=80 flags=SA seq=3 win=28960 rtt=7.1 ms
  TCP timestamp: tcpts=4294900497
  HZ seems hz=100
  System uptime seems: 497 days, 2 hours, 16 minutes, 44 seconds


--- 35.35.35.1 hping statistic ---
4 packets transmitted, 4 packets received, 0% packet loss
round-trip min/avg/max = 7.1/7.4/7.8 ms
```
Réponse de la machine pour le port 80, on obtient l'heure de démarrage pour le service du port 80. 
Avec `wget 35.35.35.1:80` on obtient la page html des challenges.
Grâce à Wireshark on observe dans le pacquet réponse le champ "Server: Apache/2.14.18 (Ubuntu)
On peut donc dire que la machine à cette adresse héberge un serveur http apache2 sur un Ubuntu.

Un scan des machins présente sur le réseau nous donne:
`nmap -sP 35.35.35.0/24`
35.35.35.1 --> www.entreprise.net
35.35.35.29 --> ftp.entreprise.net
35.35.35.42 --> zeus.entreprise.net
35.35.35.44 --> mail.entreprise.net
35.35.35.254 --> routeur3.local.lan

`traceroute entreprise.net`
24.24.24.15 --> entreprise.net
42.42.42.1 --> gateway
10.0.0.1 --> routeur1.local.lan
50.0.0.4 --> routeur4.local.lan

`traceroute www.entreprise.net`
Pas de nouvelles adresses ip

`sudo nmap --traceroute www.entreprise.net`
40.0.0.3 --> routeur3.local.lan

`sudo traceroute --tcp www.entreprise.net`
20.0.0.2 --> routeur2.local.lan

`sudo nmap -O -T4 mail.entreprise.net`
PORT    STATE SERVICE
25/tcp  open  smtp
110/tcp open  pop3
139/tcp open  netbios-ssn
445/tcp open  microsoft-ds

On a donc possiblemnt un OS Microsoft sur cette machine

`sudo nmap -A -T4 35.35.35.44`
445/tcp open  microsoft-ds Windows Server 2003 R2 3790 Service Pack 2 microsoft-ds
Donc on peut supposer que la machine mail.entreprise.net a un OS Windows Server 2003 Service Pack 2


`sudo nmpa -A -T4 35.35.35.42`
22/tcp  open          ssh     OpenSSH 7.2p2 Ubuntu 4ubuntu1 (Ubuntu Linux; protocol 2.0)
On suppose donc zeus.entreprise.net étant ayant un OS Ubuntu


`nmap -A -T4 ftp.entreprise.net`
Service Info: OS: Windows; CPE: cpe:/o:microsoft:windows


`sudo snmpwalk -c public -v 2c 50.0.0.4`
"Linux Router4 4.4.0-31-generic #50-Ubuntu SMP Wed Jul 13 00:07:12 UTC 2016 x86_64"


`sudo traceroute --tcp -p 8080 35.35.35.42`
30.0.0.3 --> routeur3.local.lan


`nmap -sP "30.0.0.0/24`
30.0.0.2 --> routeur2.local.lan


`dnsrecon -d entreprise.net`
SOA, NS 10.0.0.24 --> ns1.entreprise.net
A 24.24.24.15 --> entreprise.net

SRV http tcp 35.35.35.1 80 www.entreprise.net
SRV ldap tcp 24.24.24.15 389 entreprise.net


`dnsrecon -r 35.35.35.0-35.35.35.255`
35.35.35.21 --> oldserver.entreprise.net


Pour le schéma, pour obtenir les ip des interfaces de deux cotés d'un routeur on détermine par traceroute son passage depuis ce routeur vers un autre et on scanne la plage d'adresse.
Exemple:
```
sudo traceroute --tcp 35.35.35.1
traceroute to 35.35.35.1 (35.35.35.1), 30 hops max, 60 byte packets
 1  _gateway (42.42.42.1)  4.728 ms  4.733 ms  8.773 ms
 2  router1.local.lan (10.0.0.1)  8.781 ms  10.988 ms  13.262 ms
 3  router2.local.lan (20.0.0.2)  15.009 ms  16.701 ms  18.438 ms
 4  router3.local.lan (40.0.0.3)  21.127 ms * *
 5  www.entreprise.net (35.35.35.1)  26.692 ms  26.748 ms  26.932 ms
 6  www.entreprise.net (35.35.35.1)  28.060 ms  6.194 ms  2.021 ms
```
On va au routeur 3 par l'adresse 40.0.0.3 en passant par le routeur 2. L'interface du routeur 2 se récupère donc par le scan de la plage 40.0.0.0/24:

```
nmap -sP 40.0.0.0/24
Starting Nmap 7.70 ( https://nmap.org ) at 2018-11-07 15:53 CET
Nmap scan report for router3.local.lan (40.0.0.3)
Host is up (0.0042s latency).
Nmap scan report for router4.local.lan (40.0.0.4)
Host is up (0.0033s latency).
Nmap done: 256 IP addresses (2 hosts up) scanned in 36.87 seconds
```
Donc l'adresse du routeur 2 est 40.0.0.3 par exemple (FAUX si on ne connait pas le masque de sous-réseau)

Utiliser `sudo arp-scan --interface=vlan0 10.0.0.0/24`







```
dnsrecon -d entreprise.net -t brt -D /usr/share/dnsenum/dns.txt
[*] Performing host and subdomain brute force against entreprise.net
[*] 	 A backup.entreprise.net 192.168.1.14
[*] 	 A ftp.entreprise.net 35.35.35.29
[*] 	 A mail.entreprise.net 35.35.35.44
[*] 	 A ns1.entreprise.net 10.0.0.24
[*] 	 A www.entreprise.net 35.35.35.1
[+] 5 Records Found
```
Nouvelle adresse ip:
192.168.1.14 --> backup.entreprise.net


`sudo snmpwalk -c public -v 2c 50.0.0.4 > snmp_50.0.0.4.txt`
On peut lire rapidement et voir des champs "IpAddress" correspondant aux différentes adresses des interfaces réseau de ce routeur, suivie d'IP ressemblant à des masques de sous-réseau.

iso.3.6.1.2.1.4.20.1.1.40.0.0.4 = IpAddress: 40.0.0.4
iso.3.6.1.2.1.4.20.1.1.50.0.0.4 = IpAddress: 50.0.0.4
iso.3.6.1.2.1.4.20.1.1.127.0.0.1 = IpAddress: 127.0.0.1

puis

iso.3.6.1.2.1.4.20.1.3.40.0.0.4 = IpAddress: 255.255.255.0
iso.3.6.1.2.1.4.20.1.3.50.0.0.4 = IpAddress: 255.255.255.0
iso.3.6.1.2.1.4.20.1.3.127.0.0.1 = IpAddress: 255.0.0.0





Question 1:

DNS obtenu par la commande:
```
$ dnsrecon -d entreprise.net
[*] Performing General Enumeration of Domain: entreprise.net
[-] DNSSEC is not configured for entreprise.net
[*] 	 SOA ns1.entreprise.net 10.0.0.24
[*] 	 NS ns1.entreprise.net 10.0.0.24
[*] 	 MX mail.entreprise.net 35.35.35.44
[*] 	 A entreprise.net 24.24.24.15
[*] Enumerating SRV Records
[*] 	 SRV _http._tcp.entreprise.net www.entreprise.net 35.35.35.1 80 0
[*] 	 SRV _ldap._tcp.dc._msdcs.entreprise.net entreprise.net 24.24.24.15 389 0
[+] 2 Records Found
```

Le serveur DNS est donc ns1.entreprise.net à l'adresse 10.0.0.24

Question 2:

Par la même commande on constate que le serveur MX est le serveur "mail.entreprise.net" ) l'adresse 35.35.35.44.

Question 3:
Avec la commande
```
dnsrecon -r 35.35.35.0-35.35.35.255
```

Question 4:

ICMP: 
```
~$ sudo traceroute -I www.entreprise.net
traceroute to www.entreprise.net (35.35.35.1), 30 hops max, 60 byte packets
 1  _gateway (42.42.42.1)  3.518 ms  3.601 ms  3.705 ms
 2  router1.local.lan (10.0.0.1)  3.813 ms  3.920 ms  4.030 ms
 3  router2.local.lan (20.0.0.2)  4.137 ms  4.244 ms  4.352 ms
 4  www.entreprise.net (35.35.35.1)  6.131 ms  6.136 ms  6.726 ms
```

UDP/53:
```
~$ traceroute -U -p 53 www.entreprise.net
traceroute to www.entreprise.net (35.35.35.1), 30 hops max, 60 byte packets
 1  _gateway (42.42.42.1)  6.716 ms  6.731 ms  6.861 ms
 2  router1.local.lan (10.0.0.1)  6.940 ms  7.020 ms  7.102 ms
 3  router4.local.lan (50.0.0.4)  7.130 ms  7.212 ms  7.278 ms
 4  router3.local.lan (40.0.0.3)  7.357 ms  7.519 ms  7.523 ms
 5  * * *
 6  * * *
 7  * * *
 8  * * *
 9  * * *
10  * * *
11  * * *
12  * * *
13  * * *
14  * * *
15  * * *
16  * * *
17  * * *
18  * * *
19  * * *
20  * * *
21  * * *
22  * * *
23  * * *
24  * * *
25  * * *
26  * * *
27  * * *
28  * * *
29  * * *
30  * * *
```
Firewall?

TCP/80:
```
~$ sudo traceroute -T -p 80 www.entreprise.net
traceroute to www.entreprise.net (35.35.35.1), 30 hops max, 60 byte packets
 1  _gateway (42.42.42.1)  2.901 ms  3.007 ms  3.103 ms
 2  router1.local.lan (10.0.0.1)  3.201 ms  3.316 ms  3.369 ms
 3  router2.local.lan (20.0.0.2)  3.471 ms  3.574 ms  3.677 ms
 4  router3.local.lan (40.0.0.3)  3.829 ms  3.933 ms  4.036 ms
 5  www.entreprise.net (35.35.35.1)  4.137 ms  5.088 ms  5.187 ms
 6  www.entreprise.net (35.35.35.1)  6.048 ms  5.186 ms  2.932 ms
```

TCP/8080:
```
~$ sudo traceroute -T -p 8080 www.entreprise.net
traceroute to www.entreprise.net (35.35.35.1), 30 hops max, 60 byte packets
 1  _gateway (42.42.42.1)  2.607 ms  2.664 ms  2.781 ms
 2  router1.local.lan (10.0.0.1)  2.869 ms  2.895 ms  3.022 ms
 3  router4.local.lan (50.0.0.4)  3.120 ms  3.147 ms  3.246 ms
 4  router3.local.lan (40.0.0.3)  3.338 ms  3.505 ms  3.524 ms
 5  www.entreprise.net (35.35.35.1)  10.078 ms  10.087 ms  15.166 ms
```

Question 5:
TCP 80 est redirigé vers 8080? (raison du saut supplémentaire de TCP/80)


Question 6:
En utilisant le logiciel Zenmap, dans l'onglet détails de l'hôte on obtient la date du dernier démarrage "Mon Nov 5 07:53:18 2018


Question 7:




Question 8:


Question 9:
$ nmap -sP 35.35.35.0/24
Starting Nmap 7.70 ( https://nmap.org ) at 2018-11-07 07:56 CET
Nmap scan report for www.entreprise.net (35.35.35.1)
Host is up (0.0053s latency).
Nmap scan report for ftp.entreprise.net (35.35.35.29)
Host is up (0.040s latency).
Nmap scan report for zeus.entreprise.net (35.35.35.42)
Host is up (0.043s latency).
Nmap scan report for mail.entreprise.net (35.35.35.44)
Host is up (0.038s latency).
Nmap scan report for router3.local.lan (35.35.35.254)
Host is up (0.0015s latency).
Nmap done: 256 IP addresses (5 hosts up) scanned in 2.53 seconds

Question 10:
`nmap -sS -sT 35.35.35.1`
Doit être utilisé en super utilisateur.
