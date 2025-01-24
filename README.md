# Eclass

## Table of Contents

- [Eclass](#eclass)
  - [Table of Contents](#table-of-contents)
  - [1. Installation](#1-installation)
  - [2. Usage](#2-usage)
  - [3. Install eclass](#3-install-eclass)
  - [4. Code update](#4-code-update)

## 1. Installation

```bash
git clone https://github.com/Kostas-Xafis/eclass.git
cd eclass
```

## 2. Usage

```bash
docker compose -f docker-compose.build.yaml build
```

```bash
docker compose up -d
```

## 3. Install eclass

Visit `http://127.0.0.1/install` and follow the instructions.

## 4. Code update

To update the code inside the container, you can use the following command:

```bash
./cp_docker.sh
```
