#!/usr/bin/env ruby
require 'trollop'
require './os-lib.rb'



p generatePassword()
if userExists("Kevin")
	p "Exists"
else
	p "Not exists"
end
