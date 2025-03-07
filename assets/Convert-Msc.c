#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <windows.h>
#include <libgen.h>
#include <limits.h>

int main(int argc, char *argv[]) {
    // Obtenha o caminho do executável
    char exe_path[PATH_MAX];
    if (GetModuleFileName(NULL, exe_path, PATH_MAX) == 0) {
        perror("Erro ao obter o caminho do executável");
        return 1;
    }

    // Obtenha o diretório do executável
    char *exe_dir = dirname(exe_path);

    // Crie o caminho absoluto do arquivo index.php
    char full_path[PATH_MAX];
    snprintf(full_path, sizeof(full_path), "%s\\index.php", exe_dir);

    // Crie a linha de comando para executar o script PHP
    char command[512] = "php ";
    strcat(command, full_path);

    // Adicione os argumentos à linha de comando
    for (int i = 1; i < argc; i++) {
        strcat(command, " ");
        strcat(command, argv[i]);
    }

    // Execute a linha de comando
    int result = system(command);

    // Verifique o resultado da execução
    if (result == -1) {
        perror("Erro ao executar o comando");
        return 1;
    }

    return 0;
}
