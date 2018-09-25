#!/bin/sh

shift_params() {
    ARGS=""
    while test $# -gt 0
    do
        if [ $# -lt 5 ]
        then
            ARGS=$ARGS" $1"
        fi
        shift
    done
    echo $ARGS
}

docker_alias() {

    DOCKER_CMD='docker run --rm --network=host'
    IMAGE=$1
    WORKING_DIR=$2
    COMMAND=$3
    
    if [ -z $IMAGE ]
    then
        echo 'A first parameter $1 [IMAGE] name is required!!'
        exit 1
    fi

    if [ -z "$WORKING_DIR" ] || [ $WORKING_DIR = "" ]
    then
        WORKING_DIR=$PWD
    fi

    if [ -z "$COMMAND" ]
    then
        echo "A command name inside of $IMAGE image is required!!"
        exit 1
    elif [ $COMMAND = "composer" ]
    then
        DOCKER_CMD=$DOCKER_CMD" -v $PWD:/app"
        DOCKER_CMD=$DOCKER_CMD" -v $HOME/.composer:$HOME/.composer"
    fi


    if [ -f "$PWD/.env" ]
    then
        DOCKER_CMD=$DOCKER_CMD" --env-file $PWD/.env"
    fi

    PARAMS=`shift_params $@`
    DOCKER_CMD=$DOCKER_CMD" -i \
        -v $HOME:$HOME:ro \
        -v $PWD/wp-src:/var/www/html \
        -v $PWD/wp-content:/var/www/html/wp-content \
        -v /tmp:/tmp \
        -u $(id -u):$(id -g) \
        -w $WORKING_DIR \
        $IMAGE \
        $COMMAND $PARAMS"
    
    eval $DOCKER_CMD
}

docker_alias $@
# shift_params $@