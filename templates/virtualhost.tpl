<VirtualHost *:80>
	ServerName <%project%>.<%name%>.devdb.nl
	ServerAlias *.<%project%>.<%name%>.devdb.nl <%project%>.<%name%>.devdb.nl
	ServerAdmin k.valk@deskbookers.com
	DocumentRoot "<%basedir%>/<%name%>/web/<%project%>"

	<IfModule mpm_itk_module>
		AssignUserId <%name%> <%group%>
	</IfModule>

	<Directory "<%basedir%>/<%name%>/web/<%project%>">
		Options Indexes FollowSymLinks
		AllowOverride All
		Order allow,deny
		Allow from all
	</Directory>

	CustomLog "<%basedir%>/<%name%>/logs/<%project%>_access.txt" common
	ErrorLog "<%basedir%>/<%name%>/logs/<%project%>_error.txt"
</VirtualHost>