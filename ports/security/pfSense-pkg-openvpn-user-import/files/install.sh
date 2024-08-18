#!/bin/sh

# *
# * install.sh
# *
# * part of CTECH packages for pfSense(R) software
# * Copyright (c) 2024 CTECH
# * All rights reserved.
# *
# * Licensed under the Apache License, Version 2.0 (the "License");
# * you may not use this file except in compliance with the License.
# * You may obtain a copy of the License at
# *
# * http://www.apache.org/licenses/LICENSE-2.0
# *
# * Unless required by applicable law or agreed to in writing, software
# * distributed under the License is distributed on an "AS IS" BASIS,
# * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# * See the License for the specific language governing permissions and
# * limitations under the License.

if [ "$(uname -m)" != "amd64" ]; then
	echo "Not supported platform"
	exit
fi

if [ "$(cat /etc/version | cut -c 1-3)" == "2.7" ]; then
	pkg add https://github.com/CTECH-Informatica/pfSense-Packages/raw/main/repo/2.7/FreeBSD:14:amd64/pfSense-pkg-openvpn-user-import-1.0.0.pkg
fi

if [ "$(cat /etc/version | cut -c 1-3)" == "2.6" ]; then
	pkg add https://github.com/CTECH-Informatica/pfSense-Packages/raw/main/repo/2.6/FreeBSD:12:amd64/pfSense-pkg-openvpn-user-import-1.0.0.pkg
fi

