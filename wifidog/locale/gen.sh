for i in fr; do
    echo '<?php' > smarty.txt
    find ../templates -name "*.html" -exec ./gensmarty.pl {} >> smarty.txt \;
    find ../local_content -name "*.html" -exec ./gensmarty.pl {} >> smarty.txt \;
    find ../admin/templates -name "*.html" -exec ./gensmarty.pl {} >> smarty.txt \;
    echo '?>' >> smarty.txt

    FILE="$i/LC_MESSAGES/messages.po"
    find .. -maxdepth 1 -name "*.php" -exec xgettext --language=PHP --from-code=iso-8859-1 -j -o $FILE --keyword=_ {} \;
    for dir in admin classes include local_content login portal user_management; do
        find ../$dir -maxdepth 1 -name "*.php" -exec xgettext --language=PHP --from-code=iso-8859-1 -j -o $FILE --keyword=_ {} \;
    done
    xgettext  --language=PHP --from-code=iso-8859-1 -j -o $FILE --keyword=_ smarty.txt
done
