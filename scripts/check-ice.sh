#!/bin/bash
if [ -f /etc/profile.d/nvm.sh ]; then
    . /etc/profile.d/nvm.sh &> /dev/null
else
    if [ -f ~/.nvm/nvm.sh ]; then
        . ~/.nvm/nvm.sh &> /dev/null
    fi
fi
if [ -f ~/.profile ]; then
    . ~/.profile
fi
if [ -f ~/.bashrc ]; then
    . ~/.bashrc
fi
if [[ $(command -v brew) != "" ]]; then
    if [ -f $(brew --prefix nvm)/nvm.sh ]; then
        . $(brew --prefix nvm)/nvm.sh &> /dev/null
    fi
fi

cd ../

nvm use 18.12.1 &> /dev/null
pm2 pid coturn
