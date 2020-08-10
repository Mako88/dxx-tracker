#!/bin/bash

cd RebirthTracker

dotnet build --runtime ubuntu.18.04-x64 --configuration Release

./RebirthTracker/bin/Release/netcoreapp3.1/ubuntu.18.04-x64/RebirthTracker
