using System;
using System.Collections.Generic;
using System.Linq;
using System.Net;
using System.Net.Sockets;
using System.Text;
using System.Threading.Tasks;

namespace RebirthTracker
{
    public class Packet
    {
        private readonly List<byte> Payload;

        public Packet(byte opcode)
        {
            Payload = new List<byte>
            {
                opcode,
            };
        }

        public Packet(byte opcode, string payload)
        {
            Payload = new List<byte>
            {
                opcode,
            };
            Payload.AddRange(Encoding.UTF8.GetBytes(payload));
        }

        public Packet(byte opcode, byte payload)
        {
            Payload = new List<byte>
            {
                opcode,
                payload
            };
        }

        public Packet(byte opcode, ushort payload)
        {
            Payload = new List<byte>
            {
                opcode,
            };
            Payload.AddRange(BitConverter.GetBytes(payload));
        }

        public void Append(string payload)
        {
            Payload.AddRange(Encoding.UTF8.GetBytes(payload));
        }

        public void Append(ushort payload)
        {
            Payload.AddRange(BitConverter.GetBytes(payload));
        }

        public void Append(byte[] payload)
        {
            Payload.AddRange(payload);
        }

        public async Task Send(UdpClient client, IPEndPoint peer)
        {
            await client.SendAsync(Payload.ToArray(), Payload.Count(), peer).ConfigureAwait(false);
        }
    }
}
