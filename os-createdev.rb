#!/usr/bin/env ruby
require 'trollop'
require './os-lib.rb'

opts = Trollop::options do
	version "1.0.0"
	banner <<-EOS
Simple templated vhost creator

Usage:
	os-vhost [options] <username>+
where [options] are:
EOS
	opt :name, "Username (Bob Marley would be bmarley)", :type => String
	opt :project, "Project name", :type => String
	opt :apachedir, "Apache config directory", :default => "/etc/apache2/"
	opt :wwwdir, "www directory", :default => "/var/www/"
	opt :configuser, "User owner of virtual host config", :default => "root"
	opt :configgroup, "Group owner of virtual host config", :default => "root"
end
Trollop::die :name, "must exist" if opts[:name] == nil
Trollop::die :project, "must exist" if opts[:project] == nil

# Main run
vhost = renderVhost(opts[:name], opts[:project])
vhostName = opts[:name]+"-"+opts[:project]
vhostAvailable = opts[:apachedir]+"sites-available/"+vhostName
vhostEnabled = opts[:apachedir]+"sites-enabled/"+vhostName

createDirectory(opts[:wwwdir]+opts[:name])
createDirectory(opts[:wwwdir]+opts[:name]+opts[:project])

if writeFile(vhostAvailable, vhost)
	print("Successfully wrote vhost")
	FileUtils.ln_s(vhostAvailable, vhostEnabled)
end