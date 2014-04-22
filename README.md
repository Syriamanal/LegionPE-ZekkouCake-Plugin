LegionPE ZekkouCake Plugins
===

## Tasks done
* Core commands
 * /show and /hide
* Auth API
 * Main plugin array field $sessions
 * Non-logged-in action blockage: pre-login world for that?
* Teams API
 * Save at PocketMine-MP/LegionPE/teams/team-@teamId.yml (or json)
 * Database: players count, points
 * Join signs
 * Score bars
* Framework utils
 * Spaces (done)
* Raw map coordinates importing (semi-hardcoded)

## Tasks in progress
* More framework utils
 * Portals (TODO in each minigame; wait pocketmine entities?)
* PvP plugin
 * Port data from old plugin
 * DIFF pocketmine entities?
* Parkour plugin

## Tasks TODO
* More core commands
* Prefix API
* Spleef plugin
 * DIFF  pocketmine entities?
* CTF plugin
 * later... where is the world?

## Tasks low-priority/optional TODO
* Minigame inheritance
* Build and Guess (from Draw stuffs from MCPC)
 * Better name?

# Data storage
* Decision: --JSON-- or YAML?
* Database conversion (port amai beetroot database)

# Difficulties
* PocketMine updates
 * entities not ready
 * events for entities death and hurt
