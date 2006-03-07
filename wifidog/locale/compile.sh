for i in `find . -maxdepth 1 -mindepth 1 -type d -and -not -name ".svn"`; do
    msgfmt -o $i/LC_MESSAGES/messages.mo $i/LC_MESSAGES/messages.po
done
