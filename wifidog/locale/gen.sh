for i in `find . -maxdepth 1 -mindepth 1 -type d -and -not -name ".svn"`; do
    echo '<?php' > smarty.txt
    find ../templates -name "*.html" -exec ./gensmarty.pl {} >> smarty.txt \;
    find ../templates/classes -name "*.tpl" -exec ./gensmarty.pl {} >> smarty.txt \;
    find ../templates/sites -name "*.tpl" -exec ./gensmarty.pl {} >> smarty.txt \;
    find ../admin/templates -name "*.html" -exec ./gensmarty.pl {} >> smarty.txt \;
    echo '?>' >> smarty.txt

    FILE="$i/LC_MESSAGES/messages.po"
    touch $FILE
    find .. -maxdepth 1 -name "*.php" -exec xgettext --language=PHP --from-code=utf-8 -j -o $FILE --keyword=_ {} \;
    for dir in admin auth content cron include lib/RssPressReview login portal; do
        find ../$dir -maxdepth 1 -name "*.php" -exec xgettext --language=PHP --from-code=utf-8 -j -o $FILE --keyword=_ {} \;
    done
    find ../classes -maxdepth 3 -name "*.php" -exec xgettext --language=PHP --from-code=utf-8 -j -o $FILE --keyword=_ {} \;
    xgettext  --language=PHP --from-code=utf-8 -j -o $FILE --keyword=_ smarty.txt
done
