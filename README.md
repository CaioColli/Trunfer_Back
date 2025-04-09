
# Trunfer

Trunfer é um projeto inspirado no jogo de cartas Super Trunfo, com um sistema de turnos onde a primeira carta do baralho sempre deve ser jogada primeiro, sem opção de escolha.

🎮 Regras do Jogo

Na primeira rodada, o jogador que criou a partida começa jogando.

Nas rodadas seguintes, o vencedor da rodada anterior será o primeiro a jogar.

O ultimo jogador com todas as cartas vence.

🚀 Funcionalidades

✅ Autenticação: Login e cadastro com autenticação via Token JWT.

✅ Criação de Decks e Cartas: Personalize seus próprios baralhos.

✅ Sistema de Lobby: Criação e gerenciamento de partidas.

✅ Log de Usuário: Registro de ações dentro do sistema.

✅ Recursos de Administrador:

* Exclusão de usuários.
* Edição de permissões de usuário.

📌 Funcionalidades em Desenvolvimento

🔄 Sistema de Conquistas.

🔄 Mais opções para Administradores (ex: edição avançada de permissões de usuários).

## 💻Tecnologias Usadas💻
* PHP 
* Slim Framework
* Respect/Validation
* MySql

## 🚀Rotas Usuário🚀

Criação de Usuário **POST** **_/user/cadaster_**

Login de Usuário **POST** **_/user/login_** 

Editar perfil Usuário **PATCH** **_/user/edit_**

Editar foto perfil Usuário **POST** *_/user/edit/image*

Deletar perfl Usuário **DELETE** **_/user/edit_**

Log do Usuário **GET** **_/user/log_**

Recuperar dados Usuário **GET** **_/user_**

## 🚀Rotas Administrador🚀

**Baralho**

Criação do baralho **POST** **_/adm/decks_**

Edição do baralho **PATCH** **_/adm/decks/id_**

Deletar baralho **DELETE** **_/adm/decks/id_**

Recuperar baralhos criados **GET** **_/adm/decks_**

Recuperar baralho criado **GET** **_/adm/decks/id_**

**Carta**

_O primeiro **id** é do baralho que a carta pertence ou pertencerá_

Criação da carta **POST** **_/adm/decks/id/cards_**

Edição da carta **PATCH** **_/adm/decks/id/cards/id_**

Deletar carta **DELETE** **_/adm/decks/id/cards/id_**

Recuperar cartas criadas do baralho **GET** **_/adm/decks/id/cards_**

Recuperar carta criada **GET** **_/adm/decks/id/cards/id_**

## 🚀Rotas Lobby / Jogo🚀

**Lobby**

Criação lobby **POST** **_/lobby_**

Entrar no lobby **POST** **_/lobby/id_**

Inciar lobby **POST** **/lobby/id/start_lobby**

Edição lobby **PATCH** **_/lobby/id_**

Deletar lobby **DELETE** **_/lobby/id_**

Sair do lobby **DELETE** **_/lobby/id/player_**

Recuperar lobbies criados **GET** **_/lobby_**

Recuper lobby criado **GET** **_/lobby/id_**

**Jogo**

Distribuir cartas **POST** **_/lobby/id/distribute_cards_**

Primeira jogada da rodada **POST** **_/lobby/id/first_play_**

Demais jogadas da rodada **POST** **_/lobby/id/play_turn_**

Recuperar carta do topo do baralho **GET** **_/lobby/id/get_card_**

## 📥Instalação e execução do projeto📥

**Clone o repositório**

[Projeto Trunfer](https://github.com/CaioColli/SuperTrunfo)

**Baixe e rode o banco de dados**

[Banco de dados MySQL](https://drive.google.com/drive/folders/1L8IVHwYIZdb42WqTEN9jJuMVdAHXaz4J)

Execute o projeto e teste no POSTMAN ou uma ferramenta de sua preferência.

## ✉️Contato✉️

Em caso de duvidas ou sugestões:

GitHub: CaioColli 

[Linkedin](https://www.linkedin.com/in/caiocolli/)

