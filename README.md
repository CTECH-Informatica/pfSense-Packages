# CTECH packages for pfSense® software

## Build package

Need:

- FreeBSD machine (VM) with same version in use on pfSense
- pfSense ports
- FreeBSD ports

Install a FreeBSD on your preferred local. Connect with SSH.

To configure ports:

```shell
pkg install git
cd /root
git clone https://github.com/pfsense/FreeBSD-ports.git
mv FreeBSD-ports pfSense-FreeBSD-ports
cd pfSense-FreeBSD-ports
git checkout RELENG_2_6_0
cd ..
git clone https://github.com/freebsd/freebsd-ports.git
cd freebsd-ports
git checkout release/12.3.0
cd ..
rm -rf /usr/ports
ln -s /root/freebsd-ports /usr/ports
```

Do build:

```shell
make package -C <package_folder> DISABLE_VULNERABILITIES=yes
cp <package_folder>/work/pkg/<package>.pkg <repository_folder> 
```

## Update repository

```shell
pkg repo <repository_folder>
```

## Add CTECH repository

### pfSense 2.7

```shell
fetch -q -o /usr/local/etc/pkg/repos/pfSense_CTECH.conf https://raw.githubusercontent.com/CTECH-Informatica/pfSense-Packages/main/pfSense_CTECH.conf_27.conf
```

### pfSense 2.6

```shell
fetch -q -o /usr/local/etc/pkg/repos/pfSense_CTECH.conf https://raw.githubusercontent.com/CTECH-Informatica/pfSense-Packages/main/pfSense_CTECH.conf_26.conf
```

