if [ -n "$1" ]; then
	git add -A .
	git commit -m "$1"
	git push origin master
else
	echo "Please give the description of the push !"
fi

