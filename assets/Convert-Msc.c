#include <stdio.h>
#include <stdlib.h>
#include <string.h>

int main(int argc, char *argv[]) {
    // Crie a linha de comando para executar o script PHP
    char command[256] = "php index.php";

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
