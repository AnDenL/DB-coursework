{
  pkgs,
  ...
}:

{
  # https://devenv.sh/basics/
  env.GREET = "devenv";

  # https://devenv.sh/packages/
  packages = [ pkgs.git ];

  languages.php.enable = true;

  # https://devenv.sh/processes/
  # processes.dev.exec = "${lib.getExe pkgs.watchexec} -n -- ls -la";

  # https://devenv.sh/services/
  services.mysql = {
      enable = true;
      package = pkgs.mariadb;

      initialDatabases = [{ name = "expeditions_db"; }];

      ensureUsers = [
        {
          name = "guest";
          password = "guest";
          ensurePermissions = {
            "expeditions_db.*" = "SELECT";
          };
        }
      ];
    };

  # https://devenv.sh/scripts/
  scripts.hello.exec = ''
  '';

  # https://devenv.sh/basics/
  enterShell = ''

  '';

  # https://devenv.sh/tasks/
  # tasks = {
  #   "myproj:setup".exec = "mytool build";
  #   "devenv:enterShell".after = [ "myproj:setup" ];
  # };

  # https://devenv.sh/tests/
  enterTest = ''
  '';

  # https://devenv.sh/git-hooks/
  # git-hooks.hooks.shellcheck.enable = true;

  # See full reference at https://devenv.sh/reference/options/
}
