# Sobre a Travel B2B

Travel B2B, este serviço foi desenvolvido para resolver os desafios do gerenciamento de pedidos de viagens corporativas.

Com a utilização deste serviço, as empresas começaram a experimentar uma transformação significativa. O sistema oferece recursos de  criar, atualizar, consultar, listar, cancelar, filtrar e notificações de pedidos. Serviço com alta disponibilidade, integro, confiável e autentico.

Utilizar o serviço da Travel B2B, simplifica e minimiza riscos de operação manuais executadas nas corporações, mas também reduz custos desnecessários.

## 🚀 Começando

Essas instruções permitirão que você obtenha uma cópia do projeto em operação na sua máquina local para fins de desenvolvimento e teste.
Esta aplicação utiliza Laravel Sail para prover o ambiente de desenvolvimento Docker, padrão do Laravel, a seguir estão algumas instruções para executar a aplicação em sua máquina.

### 📋 Pré-requisitos

Antes de começar, verifique se você tem os seguintes pré-requisitos instalados:

- [Docker](https://www.docker.com/get-started) (versão 23.00 ou superior)

### 🔧 Instalação

1. **Clone o repositório**:     

```
git clone git@github.com:AndSants/travel_b2b.git
```

2. **Dependência do projeto**: 

Instale as dependências do aplicativo navegando até o diretório do aplicativo e executando o comando a seguir. Este comando usa um pequeno contêiner Docker contendo PHP e Composer para instalar as dependências do aplicativo, portanto é importante que o Docker esteja em execução em seu ambiente:

```
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```
3. **Configurando o .env**

Existe um arquivo de exemplo chamado '.env-exemple', você pode criar uma cópia dele com o nome .env na raiz do projeto e configure as variáveis de ambiente.

```
    DB_CONNECTION=mysql
    DB_HOST=mysql
    DB_PORT=3306
    DB_DATABASE=defina_nome_do_banco
    DB_USERNAME=defina_usuario_do_banco
    DB_PASSWORD=defina_senha_do_banco
```

4. **Build do projeto**:  

Finalmente, você pode iniciar o Sail. Verifique documentação do Laravel para configurar um alias que permita executar os comandos do Sail mais facilmente.

```
4.1. Inicie o container
./vendor/bin/sail up -d
```
> [!IMPORTANT]\
> Lembre-se de executar a migração (`./vendor/bin/sail php artisan migrate`) após o build do projeto.

```
4.2. Gere a nova chave do projeto
APP_KEY= (./vendor/bin/sail php artisan key:generate)
```
```
4.3. Atualize o composer com a biblioteca
./vendor/bin/sail composer require php-open-source-saver/jwt-auth
4.4. Gere a chave JWT
JWT_SECRET= (./vendor/bin/sail php artisan jwt:secret)
```

5. **Configuração do MailTrap**: 
O Mailtrap é uma ótima ferramenta para testar o envio de e-mails em ambientes de desenvolvimento, pois ele simula um servidor SMTP e captura os e-mails enviados pela aplicação, sem enviá-los para destinatários reais. Vamos configurar o Laravel para usar o Mailtrap.

```
5.1. Acesse o site do [Mailtrap](https://mailtrap.io/).
5.2. Crie uma conta gratuita ou faça login.
5.3. No painel do Mailtrap, crie um novo Inbox (caixa de entrada) para o seu projeto.
5.4. No arquivo .env do seu projeto Laravel, atualize as configurações de e-mail com as credenciais (username e password), confirme o host e porta do Mailtrap:

MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=seu_username
MAIL_PASSWORD=seu_password
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

```
5.5. Você pode testar o envio de e-mails usando o Tinker: ./vendor/bin/sail php artisan tinker
5.6. No console do Tinker, execute o seguinte código:
Mail::raw('Este é um e-mail de teste!', function ($message) {
    $message->to('test@example.com')->subject('Teste de E-mail');
});
5.7. Execute o comando abaixo para criar a tabela de filas:
./vendor/bin/sail php artisan queue:work
```

> [!IMPORTANT]\
> Esta disponível a collection da API na raiz do projeto, exportada via postman.

```
Para executar os teste:
./vendor/bin/sail php artisan test --coverage
```

## 😊 Pronto

* Até esse passo o sistema deverá está acessível, conto com você para evoluirmos juntos essa ferramenta incrível.

## 📌 Versão

Para as versões disponíveis, observe o [Changelog](https://github.com/AndSants/travel_b2b/blob/main/changelog.md). 

## ✒️ Autores

| Nome | Email |Linkedin |
|-|-|-|
| André Santos | <andresantos.iron@gmail.com> |[linkedin](https://www.linkedin.com/in/andresantostech/)|

## 📄 Licença Privada

### Definição

Esta licença é uma licença privada e não open source. O uso do código-fonte deste projeto é restrito.

### Termos de Uso

- Para mais informações ligue 81 9 9704-4667

### Proibições

- É proibido o uso comercial deste código por terceiros sem autorização expressa.
- A redistribuição do código-fonte ou partes dele é estritamente proibida.

### Consequências da Violação

Qualquer violação dos termos desta licença resultará em ações legais conforme necessário.