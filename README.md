# extas-repositories-file
Extas compatable File repository package

# Usage

Add driver spec to your drivers.json or just copy default:

```bash
copy /vendor/jeyroik/extas-repositories-file/resources/drivers.dist.json resources/drviers.json
```

Set env:

```bash
EXTAS__DRIVER = "file"

# replace ".json" with your db extension. Please, do not forgot a "." as an extension prefix
EXTAS__DSN = "absolute/path/to/db/.json"
EXTAS__DB = "extas"
```