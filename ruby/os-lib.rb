require 'erb'
require 'fileutils'

def openErb(file)
	fh = File.open(file)
	erb = ERB.new(fh.read)
	return erb
end

def renderVhost(name, project)
    @project = project
    @name = name
	
	erb = openErb('templates/virtualhost.erb')
	return erb.result( binding )
end

def writeFile(name, content)
	begin
		file = File.open(name, "w")
		file.write(content) 
	rescue IOError => e
		return false
	ensure
		file.close unless file == nil
	end
	FileUtils.chown(opts[:configuser], opts[:configgroup], name)
	return true
end

def createDirectory(directory)
	if File.exists?(directory)
		if !File.directory?(directory)
			FileUtils.mv(directory, directory + ".file")
			FileUtils.mkdir(directory)
		end
	else
		FileUtils.mkdir(directory)
	end
end

def generatePassword()
	o =  [('a'..'z'),('A'..'Z'),(0..9)].map{|i| i.to_a}.flatten
	(0...10).map{ o[rand(o.length)] }.join
end

def userExists(user)
	return (`grep "^#{user}:" /etc/passwd` != "")
end

def userCreate(user)

end