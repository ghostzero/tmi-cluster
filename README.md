# TMI Cluster for Twitch Chatbots

<p align="center">
  <img height="500" src="https://cdn.jsdelivr.net/gh/ghostzero/tmi-website@main/docs/images/tmi_cluster.png">
</p>

<p align="center">
  <a href="https://packagist.org/packages/ghostzero/tmi-cluster"><img src="https://img.shields.io/packagist/dt/ghostzero/tmi-cluster" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/ghostzero/tmi-cluster"><img src="https://img.shields.io/packagist/v/ghostzero/tmi-cluster" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/ghostzero/tmi-cluster"><img src="https://img.shields.io/packagist/l/ghostzero/tmi-cluster" alt="License"></a>
  <a href="https://discord.gg/qsxVMNg"><img src="https://discordapp.com/api/guilds/552952675369484301/embed.png?style=shield" alt="Discord"></a>
</p>

## Introduction

TMI Cluster is a Laravel package that smoothly enables a highly scalable IRC client cluster for Twitch. TMI Cluster consists of multiple supervisors that can be deployed on multiple hosts. The core is inspired by [Horizon](https://github.com/laravel/horizon), which handles the complex IRC process management.

The cluster stores its data in the database and has a Redis Command Queue to send IRC commands and receive messages.

## Features

- Supervisor can be deployed on multiple servers
- Up-to-date Twitch IRC Client written in PHP 7.4
- Scalable message input/output queue
- Advanced cluster status dashboard
- Channel management and invite screen

## PHP Twitch Messaging Interface

The TMI Cluster is powered by the [PHP Twitch Messaging Interface](https://github.com/ghostzero/tmi) client to communicate with Twitch. It's a full featured, high performance Twitch IRC client written in PHP 7.4. 

## Official Documentation

You can view our official documentation [here](https://tmiphp.com/docs/tmi-cluster.html).
