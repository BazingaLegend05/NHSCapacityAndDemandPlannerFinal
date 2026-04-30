# NHSCapacityAndDemandPlannerFinal
*For Editors*
1. Clone repository on vscode, or if you already have a repository open from a previous session, pull contents from github.
2. If it is not already running in the container, click the search bar and click 'reopen in container'.
3. Once in the container you will see options at the bottom right window where you can view problems, outputs, debug console, terminal and ports.
4. Before editing, go to the backup.sql, select all and rightclick and press run, this will restore the database for you.
5. To preview work done, hover over the internet icon next to port 8000 and press open in browser, add /filename to the end of the url to view specific pages.
6. To preview the database in myphpadmin, do the same for port 8080.
7. To add changes to the database, in the mysql dropdown in the bottom left, right click the db and click new query. Type your sql commands, highlight them then right click and press run. You can also edit it in the phpmyadmin port.
8. If any changes are made to the database, run this code in the vscode container terminal to backup the database before committing changes or the changes will not be made for everyone.
    /nl mariadb-dump -h db -u mariadb -p mariadb > backup.sql --skip-ssl
    /nl When prompted to enter a password, enter 'mariadb', it will not look like you are typing anything - this is because of shoulder surfing protections in the extention.
9. Ensure to pull changes before pushing your commit. If changes are pulled, ensure they do not collide with the work you have done before you commit.

*For users (ONLY DO WHEN MARKED COMPLETE)*
1. Download zip file and extract into a file of your choosing.
2. Open the file in vscode
3. in the search bar click 'reopen in container' if it is not already doing so.
4. from the ports section of the bottom right window, hover over the 8000 port internet icon and click to open in browser.
5. to log in as administrator username and password is: admin 1. for a regular user, username and password is user1
6. ###### COMPLETE WHEN DONE
