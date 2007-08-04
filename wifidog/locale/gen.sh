echo "Extracting strings from Smarty templates"
echo '<?php' > smarty.txt
find ../templates -name "*.html" -exec ./gensmarty.pl {} >> smarty.txt \;
find ../templates/classes -name "*.tpl" -exec ./gensmarty.pl {} >> smarty.txt \;
find ../templates/sites -name "*.tpl" -exec ./gensmarty.pl {} >> smarty.txt \;
find ../admin/templates -name "*.html" -exec ./gensmarty.pl {} >> smarty.txt \;
echo '?>' >> smarty.txt
POT_FILE="message.pot"
	rm -f $POT_FILE
	echo "Creating new .POT file"
	find .. -maxdepth 1 -name "*.php" -exec xgettext --language=PHP --from-code=utf-8 -o $POT_FILE --keyword=_ {} \;
	for dir in admin auth content cron include lib/feedpressreview login portal; do
		find ../$dir -maxdepth 1 -name "*.php" -exec xgettext --language=PHP --from-code=utf-8 -j -o $POT_FILE --keyword=_ {} \;
	done
	find ../classes -maxdepth 3 -name "*.php" -exec xgettext --language=PHP --from-code=utf-8 -j -o $POT_FILE --keyword=_ {} \;
	xgettext  --language=PHP --from-code=utf-8 -j -o $POT_FILE --keyword=_ smarty.txt

for i in `find . -maxdepth 1 -mindepth 1 -type d -and -not -name ".svn"`; do
	FINAL_FILE="$i/LC_MESSAGES/messages.po"
	echo "Merging with previous $i .PO file"
	msgmerge --update $FINAL_FILE $POT_FILE 
done
