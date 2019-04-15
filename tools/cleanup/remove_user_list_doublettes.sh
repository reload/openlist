#!/bin/bash

for((i=0;i<=255;i++))
do
   hex=$(printf '%02x' $i)
   echo "Step $i / 255 ($hex)"
   php -f remove_user_list_doublettes.php "pattern=$hex" "test=0"
done
