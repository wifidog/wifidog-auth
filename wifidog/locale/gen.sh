for i in *_*; do
    echo > smarty.txt
    find ../templates -name "*.html" -exec ./gensmarty.pl {} >> smarty.txt \;
    find ../local_content -name "*.html" -exec ./gensmarty.pl {} >> smarty.txt \;

    FILE="$i/LC_MESSAGES/messages.po"
    if [ -f $FILE ]; then
        find .. -name "*.php" -exec xgettext -C -j -o $FILE --keyword=_ {} \;
        xgettext -C -j -o $FILE --keyword=_ smarty.txt
    else
        find .. -name "*.php" -exec xgettext -C -o $FILE --keyword=_ {} \;
        xgettext -C -j -o $FILE --keyword=_ smarty.txt
    fi

done
