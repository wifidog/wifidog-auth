for i in "fr"; do
    msgfmt -o $i/LC_MESSAGES/messages.mo $i/LC_MESSAGES/messages.po
done
