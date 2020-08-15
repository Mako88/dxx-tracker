using System;
using System.Collections.Generic;
using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;
using System.Linq;
using System.Net;
using System.Net.Sockets;
using System.Text;
using System.Threading.Tasks;

namespace RebirthTracker
{
    /// <summary>
    /// A hosted game
    /// </summary>
    public class Game
    {
        /// <summary>
        /// The game's ID
        /// </summary>
        [Key]
        [DatabaseGenerated(DatabaseGeneratedOption.None)]
        public ushort ID { get; set; }

        /// <summary>
        /// The game's header string
        /// </summary>
        public string Header { get; set; }

        /// <summary>
        /// Binary game data
        /// </summary>
        public byte[] Blob { get; set; }

        /// <summary>
        /// Whether or not this game is new
        /// </summary>
        [NotMapped]
        public bool IsNew { get; set; }

        /// <summary>
        /// The time the game was hosted
        /// </summary>
        public DateTime LastUpdated { get; set; }

        /// <summary>
        /// The port the game is hosted on
        /// </summary>
        public int Port { get; set; }

        /// <summary>
        /// The IP Address in storage format
        /// </summary>
        public byte[] IPAddressData { get; set; }

        /// <summary>
        /// The IP Address the game is hosted on
        /// </summary>
        [NotMapped]
        public IPAddress IPAddress
        {
            get
            {
                if (ipAddress == null && IPAddressData != null)
                {
                    ipAddress = new IPAddress(IPAddressData);
                }
                return ipAddress;
            }
        }
        private IPAddress ipAddress;

        /// <summary>
        /// The endpoint the game is hosted on
        /// </summary>
        [NotMapped]
        public IPEndPoint Endpoint
        {
            get
            {
                if (endpoint == null && IPAddress != null)
                {
                    endpoint = new IPEndPoint(IPAddress, Port);
                }
                return endpoint;
            }
        }
        private IPEndPoint endpoint;

        /// <summary>
        /// The game host as a string
        /// </summary>
        public string HostString { get; set; }

        /// <summary>
        /// The game mode of the game
        /// </summary>
        public GameMode GameMode { get; set; }

        /// <summary>
        /// The version as a string for storing
        /// </summary>
        public string VersionString { get; set; }

        /// <summary>
        /// The level number of the hosted game
        /// </summary>
        public uint LevelNumber { get; set; }

        /// <summary>
        /// The difficulty of the game
        /// </summary>
        public Difficulty Difficulty { get; set; }

        /// <summary>
        /// The current game status
        /// </summary>
        public string Status { get; set; }

        /// <summary>
        /// How many players are connected to the game
        /// </summary>
        public byte NumConnected { get; set; }

        /// <summary>
        /// Maximum number of players allowed in the game
        /// </summary>
        public byte MaxPlayers { get; set; }

        /// <summary>
        /// The name of the Game
        /// </summary>
        public string Name { get; set; }

        /// <summary>
        /// The title of the mission
        /// </summary>
        public string MissionTitle { get; set; }

        /// <summary>
        /// The name of the mission
        /// </summary>
        public string MissionName { get; set; }

        /// <summary>
        /// Which version of Descent the game is hosted in
        /// </summary>
        public int DescentVersion { get; set; }

        /// <summary>
        /// Empty constructor is necessary for Entity Framework
        /// </summary>
        public Game()
        {

        }

        /// <summary>
        /// Constructor
        /// </summary>
        public Game(ushort gameID, byte[] packet, IPEndPoint peer)
        {
            ID = gameID;
            Update(packet, peer);
            IsNew = true;
        }

        /// <summary>
        /// Update a game with new info
        /// </summary>
        public void Update(byte[] packet, IPEndPoint peer)
        {
            var rawString = Encoding.UTF8.GetString(packet);

            int from = rawString.IndexOf("b=") + 2;
            int to = rawString.IndexOf(",", from);

            Header = rawString.Substring(from, to - from);

            VersionString = Header.Replace('R', ' ');

            Blob = packet.Skip(to + 3).ToArray();

            if (!Blob.Any())
            {
                throw new ArgumentNullException("Packet had no blob");
            }

            UnpackBlob();

            LastUpdated = DateTime.Now;

            IPAddressData = peer.Address.GetAddressBytes();
            Port = peer.Port;
            HostString = $"{Endpoint}";

            DescentVersion = Header.ToLowerInvariant().IndexOf("d1x") == -1 ? 2 : 1;

            IsNew = false;
        }

        /// <summary>
        /// Populate properties with info from the blob
        /// </summary>
        private void UnpackBlob()
        {
            // 0 - UPID 

            // 1-2 - Major Version
            // 3-4 - Minor Version
            // 5-6 - Micro Version

            // 7-10 - GameID

            LevelNumber = BitConverter.ToUInt32(Blob, 11); // 11 - 14

            GameMode = (GameMode) Blob[15];

            // 16 - RefusePlayers

            Difficulty = (Difficulty) Blob[17];

            // 18 - Status

            NumConnected = Blob[19];

            MaxPlayers = Blob[20];

            Status = GetStatus(Blob[16], Blob[18], Blob[21]); // 21 - Flag

            SetStrings(Blob.Skip(22)); // 22-* - Game Name, Mission Title, and Mission Name
        }

        /// <summary>
        /// Get a status string. This logic is copied from Rebirth
        /// </summary>
        private string GetStatus(byte refusePlayers, byte status, byte flag)
        {
            if (status == 4)
            {
                return "Forming";
            }

            if (status == 1)
            {
                if (refusePlayers == 1)
                {
                    return "Restricted";
                }

                if (flag == 5)
                {
                    return "Closed";
                }

                return "Open";
            }

            return "Between";
        }

        /// <summary>
        /// Set Mission, Title, and Name
        /// </summary>
        private void SetStrings(IEnumerable<byte> stringBytes)
        {
            var strings = Encoding.UTF8.GetString(stringBytes.ToArray());

            Name = GetSubstring(strings, 0, 15);
            MissionTitle = GetSubstring(strings, Name.Length + 1, 25);
            MissionName = GetSubstring(strings, Name.Length + MissionTitle.Length + 2, 25);
        }

        /// <summary>
        /// Get up to length characters in a string before a '\0' character
        /// </summary>
        private string GetSubstring(string theString, int start, int length)
        {
            var newString = string.Empty;

            if (start < theString.Length)
            {
                newString = theString.Substring(start);

                var newLength = Math.Min(Math.Min(newString.Length, length), newString.IndexOf('\0') + 1);

                return newString.Substring(0, newLength).Trim('\0');
            }

            return newString;
        }

        /// <summary>
        /// Send the game to the client
        /// </summary>
        public async Task SendGame(UdpClient client, IPEndPoint peer)
        {
            var packet = new Packet(24, $"a={IPAddress}/{Port},c=");

            packet.Append(ID);
            packet.Append(",z=");
            packet.Append(Blob);

            await packet.Send(client, peer).ConfigureAwait(false);
        }

        /// <summary>
        /// Saves the game to the database
        /// </summary>
        public async Task Save()
        {
            using (var db = new GameContext())
            {
                if (IsNew)
                {
                    db.Add(this);
                }
                else
                {
                    db.Update(this);
                }

                await db.SaveChangesAsync().ConfigureAwait(false);
            }
        }
    }

    /// <summary>
    /// The mode of the game
    /// </summary>
    public enum GameMode
    {
        Anarchy,
        TeamAnarchy,
        RoboAnarchy,
        Cooperative,
        CaptureTheFlag,
        Hoard,
        TeamHoard,
        Bounty,
    }

    /// <summary>
    /// The difficulty of the game
    /// </summary>
    public enum Difficulty
    {
        Trainee,
        Rookie,
        Hotshot,
        Ace,
        Insane,
    }
}
