#!/usr/bin/perl -w

open INPUT, "<$ARGV[0]";
while(<INPUT>) {
    while ($_ =~ m/{"(.*?)"|_.*}/) {
        if ($1) {
            print "_(\"$1\")\n";
        }
        $_ = $';
    }
}
close INPUT;
