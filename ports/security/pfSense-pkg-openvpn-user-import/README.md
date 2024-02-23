# OpenVPN Client Export for pfSense sofware

# Install instructions

If you enabled the Unofficial repo, you can add this package under System -> Package Manager

Or add it under console/ssh.

cd /root

fetch https://raw.githubusercontent.com/CTECH-Informatica/pfSense-Packages/main/ports/ctech-pkg-openvpn-user-import/files/install_26.sh

sh ./install_26.sh

Once it finishes, all must be in place. If you do not see the menu after it finishes, try to install any pfSense package from GUI, like cron for example.
