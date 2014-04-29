LegionPE ZekkouCake Plugin
===
This repo includes the core utils and commands, auth, portals, 4 minigames (KitPvP, parkour, spleef and CTF), chat channels and prefixes, ranks api and teams api

# Tasks

## Tasks done
* Core commands
 * /show and /hide
 * /auth
 * /quit (Minigame implementation)
* Auth API
 * Main plugin array field $sessions
 * Non-logged-in action blockage: pre-login world for that?
 * IP auth customization
* Teams API
 * Save at PocketMine-MP/LegionPE/teams/team-@teamId.yml (or json)
 * Database: players count, points
 * Join signs
 * Score bars
* Core utils
 * Spaces
 * Event simplifier
* Raw map coordinates importing (semi-hardcoded)
* Parkour plugin
 * /stats
* PvP plugin
 * /kills [top]
 * /pvp
* Prefix
* Amai Beetroot data conversion
 * PvP items ans armor config conversion aborted and will not be done

## Tasks in progress
* Permissions
* More core utils
 * What exactly are they? #_#
* DIFF pocketmine entities?
* Spleef
 * Commands to leave and join
 * Monitor player sessions using events
 * Getter functions
 * Signs
 * Start/stop loop initialized by Arena::activate(), looped by Arena::start() and Arena::stop(), finalized by unsetting object of Arena::deactivate()
* CTF plugin
 * later... where is the world? Hello @SpyDuck?
* Minigame inheritance
* More core commands
 * /chat, /mute and /unmute - chat channels

## Tasks TODO
* @_@ not debugging... but debugging of course...

## Tasks low-priority/optional TODO
* Build and Guess (from Draw stuffs from MCPC)
 * Better name?

===
# Difficulties
* PocketMine updates
 * entities not ready
 * events for entities death and hurt
* Raw locations
 * minigame coords

===
# Debugging

## Items
(sorted by priorities)

* Syntax errors
* Multiplayer bugs
* Typos

## Done
* None of course :(

# Permissions
LegoinPE:
* legionpe.mg
 * legionpe.mg.**.join
* legionpe.cmd
 * legionpe.cmd.auth
 * legionpe.cmd.players (remove this?)
  * legionpe.cmd.players.show
  * leginope.cmd.players.hide
 * legionpe.cmd.mg.**.***

PvP:

* legionpe.mg.pvp
 * legionpe.mg.pvp.spawnattack
* legionpe.cmd.mg.pvp
 * legionpe.cmd.mg.pvp.pvp
 * legionpe.cmd.mg.pvp.kills

Chat channels:

* legionpe.chat
 * legionpe.chat.mandatory (always true)
 * legionpe.chat.general
 * legionpe.chat.mute
  * legionpe.chat.mute.{CID}
 * legionpe.chat.team.{TID}
 * legionpe.chat.pvp.public
 * legionpe.chat.pvp.{TID}
 * legionpe.chat.pk.public
 * legionpe.chat.pk.{TID}
 * legionpe.chat.ctf.public
 * legionpe.chat.ctf.{TID}
 * legionpe.chat.spleef.public
 * legionpe.chat.spleef.[{SID}].[{TID}]

# Appendix:
* IDS
 * SID: SpleefArena ID
 * TID: Team ID
 * CID: Client ID
 * EID: Entity ID
 * PID: Packet ID
