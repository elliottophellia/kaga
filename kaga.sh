#!/bin/sh

clear

# Print banner
cat <<"EOF"
d8b                                     
?88                                     
 88b  FREE UNLIMITED REVERSE IP LOOKUP
 888  d88' d888b8b   d888b8b   d888b8b  
 888bd8P' d8P' ?88  d8P' ?88  d8P' ?88  
d88888b   88b  ,88b 88b  ,88b 88b  ,88b 
d88' `?88b,`?88P'`88b`?88P'`88b`?88P'`88b
by @elliottophellia        )88          
                          ,88P          
                      `?8888P           
EOF

# Check for necessary commands and install if missing
for cmd in curl jq sed shuf; do
    if ! command -v $cmd >/dev/null 2>&1; then
        echo "$cmd is not installed. Installing..."

        echo "Check if user is using doas or sudo..."
        # Check if doas or sudo is available
        if command -v doas >/dev/null 2>&1; then
            sudo="doas"
        elif command -v sudo >/dev/null 2>&1; then
            sudo="sudo"
        else
            echo "Neither doas nor sudo is available. Please install one of them and try again."
            exit 1
        fi
        echo "$sudo is detected. Installing $cmd..."
        echo "Check which package manager is available..."
        # Check if apt or yum is available
        if command -v apt >/dev/null 2>&1; then
            pkg_manager="apt"
        elif command -v pacman >/dev/null 2>&1; then
            pkg_manager="pacman"
        elif command -v dnf >/dev/null 2>&1; then
            pkg_manager="dnf"
        elif command -v pkg >/dev/null 2>&1; then
            pkg_manager="pkg"
        elif command -v brew >/dev/null 2>&1; then
            pkg_manager="brew"
        else
            echo "None of the known package managers are available. Please install the missing dependencies manually."
            exit 1
        fi
        echo "$pkg_manager is detected. Installing $cmd..."
        if [ "$pkg_manager" = "pacman" ]; then
            $sudo $pkg_manager -S $cmd
        else
            $sudo $pkg_manager install $cmd
        fi
    fi
done

# Get user input
read -p "Input your Domain/IP Address: " userInput

# Remove http:// or https://
userInput=$(echo "$userInput" | sed 's/http(s)?:\/\///g')

# Validate if the input is a valid domain
if echo "$userInput" | grep -Eq "^[A-Za-z0-9.-]+\.[A-Za-z]{2,}$"; then
    # Replace the domain in the URI with the user input
    uri="https://reverseip.rei.my.id/$userInput"
    response=$(curl -s "$uri")

    # Check if the response contains the RequestResult property
    if echo "$response" | jq -e .RequestResult >/dev/null 2>&1; then
        echo "$response" | jq -r '.RequestResult.ResultDomainList | join("\n")'
        if [ ! -d "output" ]; then
            mkdir output
        fi
        randomMathNumber=$(shuf -i 1-10000 -n 1)
        echo "$response" | jq -r '.RequestResult.ResultDomainList | join("\n")' >output/kaga_"$userInput"_"$randomMathNumber".txt
        echo "Results saved to output/kaga_"$userInput"_"$randomMathNumber".txt"
    else
        echo "Invalid domain or IP address"
    fi
else
    echo "Invalid domain or IP address"
fi
